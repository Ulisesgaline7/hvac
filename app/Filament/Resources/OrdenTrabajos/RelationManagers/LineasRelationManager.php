<?php

namespace App\Filament\Resources\OrdenTrabajos\RelationManagers;

use App\Services\FichaService;
use App\Services\FormularioDinamico;
use DomainException;
use Illuminate\Support\Facades\Storage;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LineasRelationManager extends RelationManager
{
    protected static string $relationship = 'lineas';

    protected static ?string $title = 'Equipos y fichas técnicas';

    private function prefijar(array $respuestas): array
    {
        $datos = [];
        foreach ($respuestas as $k => $v) {
            $datos['r_' . $k] = $v;
        }

        return $datos;
    }



    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('equipo.codigo')->label('Equipo')->placeholder('Por instalar'),
                TextColumn::make('equipo.tipo.nombre')->label('Tipo')->placeholder('—'),
                TextColumn::make('marca_modelo')->label('Marca y modelo')->placeholder('—')
                    ->state(fn ($record) => $record->equipo
                        ? trim(($record->equipo->marca?->nombre ?? '') . ' ' . ($record->equipo->modelo ?? '')) ?: null
                        : null),
                TextColumn::make('capacidad')->label('Capacidad')->placeholder('—')
                    ->state(fn ($record) => $record->equipo?->capacidad_valor
                        ? rtrim(rtrim((string) $record->equipo->capacidad_valor, '0'), '.') . ' ' . $record->equipo->capacidad_unidad
                        : null),
                TextColumn::make('ubicacion')->label('Ubicación')->placeholder('—')
                    ->state(fn ($record) => \App\Models\Area::find($record->equipo?->area_id ?? $record->area_id)?->nombre),
                TextColumn::make('tareas')->label('Tareas previstas')->placeholder('—')->wrap()
                    ->state(fn ($record) => $record->datos_previstos['tareas'] ?? null),
                IconColumn::make('ficha.completa')->label('Ficha completa')->boolean(),
                TextColumn::make('ficha.version.version')->label('Versión')->prefix('v')->placeholder('—'),
            ])
            ->recordActions([
                Action::make('tareas')
                    ->label('Tareas previstas')
                    ->icon('heroicon-o-list-bullet')
                    ->fillForm(fn ($record) => ['tareas' => $record->datos_previstos['tareas'] ?? ''])
                    ->schema([\Filament\Forms\Components\Textarea::make('tareas')->label('Tareas previstas')->rows(4)])
                    ->action(function (array $data, $record) {
                        $record->update(['datos_previstos' => array_merge($record->datos_previstos ?? [], ['tareas' => $data['tareas']])]);
                        Notification::make()->success()->title('Tareas previstas guardadas')->send();
                    }),
                Action::make('ficha')
                    ->label('Llenar ficha')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->modalWidth('4xl')
                    ->fillForm(function ($record) {
                        try {
                            return $this->prefijar(app(FichaService::class)->obtenerOCrear($record)->respuestas ?? []);
                        } catch (DomainException) {
                            return [];
                        }
                    })
                    ->schema(function ($record) {
                        try {
                            $ficha = app(FichaService::class)->obtenerOCrear($record);
                        } catch (DomainException) {
                            return [];
                        }

                        return FormularioDinamico::componentes($ficha->version->definicion);
                    })
                    ->action(function (array $data, $record) {
                        try {
                            $ficha = app(FichaService::class)->obtenerOCrear($record);
                        } catch (DomainException $e) {
                            Notification::make()->danger()->title($e->getMessage())->send();

                            return;
                        }

                        $respuestas = [];
                        foreach ($data as $k => $v) {
                            if (str_starts_with($k, 'r_') && $v !== null && $v !== '' && $v !== []) {
                                $respuestas[substr($k, 2)] = $v;
                            }
                        }

                        $svc = app(FichaService::class);

                        try {
                            $svc->adjuntar($record, $data);
                        } catch (DomainException $e) {
                            Notification::make()->danger()->title($e->getMessage())->send();

                            return;
                        }

                        $ficha = $svc->recalcular($svc->guardar($ficha, $respuestas));

                        Notification::make()->success()
                            ->title($ficha->completa ? 'Ficha completa' : 'Borrador guardado (faltan campos obligatorios)')
                            ->send();

                        foreach ($ficha->alertas ?? [] as $alerta) {
                            Notification::make()->warning()->title($alerta)->persistent()->send();
                        }
                    }),
            ]);
    }
}
