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
            ->headerActions([
                Action::make('agregar_equipo')
                    ->label('Agregar equipo')
                    ->icon('heroicon-o-plus')
                    ->visible(fn () => in_array($this->getOwnerRecord()->estado, [
                        \App\Enums\EstadoOt::Asignada, \App\Enums\EstadoOt::EnProceso, \App\Enums\EstadoOt::EnEspera,
                    ], true))
                    ->modalWidth('2xl')
                    ->schema(fn () => [
                        \Filament\Forms\Components\Select::make('area_id')->label('Área')->required()
                            // Se lee en modo interno: el técnico solo ve las áreas de sus equipos.
                            ->options(fn () => \App\Providers\RlsServiceProvider::comoInterno(
                                fn () => \App\Models\Area::where('sucursal_id', $this->getOwnerRecord()->sucursal_id)
                                    ->orderBy('nombre')->pluck('nombre', 'id')->all()
                            )),
                        \Filament\Forms\Components\Select::make('tipo_equipo_id')->label('Tipo de equipo')->required()->searchable()
                            ->options(fn () => \App\Models\TipoEquipo::orderBy('nombre')->pluck('nombre', 'id')->all()),
                        \Filament\Forms\Components\Select::make('marca_id')->label('Marca')->searchable()
                            ->options(fn () => \App\Models\Marca::orderBy('nombre')->pluck('nombre', 'id')->all()),
                        \Filament\Forms\Components\TextInput::make('modelo'),
                        \Filament\Forms\Components\TextInput::make('serie'),
                        \Filament\Forms\Components\TextInput::make('capacidad_valor')->label('Capacidad')->numeric(),
                        \Filament\Forms\Components\TextInput::make('capacidad_unidad')->label('Unidad (BTU, TR, kW)'),
                        \Filament\Forms\Components\Select::make('refrigerante_id')->label('Refrigerante')
                            ->options(fn () => \App\Models\Refrigerante::orderBy('nombre')->pluck('nombre', 'id')->all()),
                        \Filament\Forms\Components\TextInput::make('voltaje'),
                        \Filament\Forms\Components\TextInput::make('corriente'),
                    ])
                    ->action(function (array $data) {
                        try {
                            $equipo = app(\App\Services\RegistroEquipoCampo::class)
                                ->registrar($this->getOwnerRecord(), auth()->user(), $data);
                        } catch (DomainException $e) {
                            Notification::make()->danger()->title($e->getMessage())->send();

                            return;
                        }

                        Notification::make()->success()
                            ->title("Equipo {$equipo->codigo} registrado y agregado a la OT")
                            ->actions([
                                Action::make('etiqueta')->label('Imprimir etiqueta')
                                    ->url(route('etiquetas.equipo', $equipo))->openUrlInNewTab(),
                            ])
                            ->persistent()
                            ->send();
                    }),
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
                Action::make('movimiento')
                    ->label('Refrigerante')
                    ->icon('heroicon-o-beaker')
                    ->visible(fn ($record) => in_array($record->ordenTrabajo?->estado, [\App\Enums\EstadoOt::EnProceso, \App\Enums\EstadoOt::EnEspera], true))
                    ->schema([
                        \Filament\Forms\Components\Select::make('cilindro_id')
                            ->label('Cilindro')
                            ->options(fn () => \App\Models\CilindroRefrigerante::with('refrigerante')
                                ->where('estado', 'activo')->get()
                                ->mapWithKeys(fn ($c) => [$c->id => "{$c->codigo} · {$c->refrigerante?->nombre} · saldo {$c->saldoKg()} kg"]))
                            ->searchable(),
                        \Filament\Forms\Components\Select::make('tipo')
                            ->options(['carga' => 'Carga (agregado)', 'recuperacion' => 'Recuperación'])
                            ->required(),
                        \Filament\Forms\Components\TextInput::make('cantidad_kg')
                            ->label('Cantidad')->numeric()->minValue(0.01)->suffix('kg')->required(),
                        \Filament\Forms\Components\Textarea::make('observaciones'),
                    ])
                    ->action(function (array $data, $record) {
                        try {
                            \App\Models\MovimientoRefrigerante::create([
                                'orden_trabajo_id' => $record->orden_trabajo_id,
                                'orden_trabajo_equipo_id' => $record->id,
                                'cilindro_id' => $data['cilindro_id'] ?? null,
                                'tipo' => $data['tipo'],
                                'cantidad_kg' => $data['cantidad_kg'],
                                'observaciones' => $data['observaciones'] ?? null,
                            ]);
                        } catch (DomainException $e) {
                            Notification::make()->danger()->title($e->getMessage())->send();

                            return;
                        }

                        Notification::make()->success()->title('Movimiento registrado')->send();
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
