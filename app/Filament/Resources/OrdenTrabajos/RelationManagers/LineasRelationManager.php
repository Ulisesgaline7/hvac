<?php

namespace App\Filament\Resources\OrdenTrabajos\RelationManagers;

use App\Services\FichaService;
use App\Services\FormularioDinamico;
use DomainException;
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
                IconColumn::make('ficha.completa')->label('Ficha completa')->boolean(),
                TextColumn::make('ficha.version.version')->label('Versión')->prefix('v')->placeholder('—'),
            ])
            ->recordActions([
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

                        $ficha = app(FichaService::class)->guardar($ficha, $respuestas);

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
