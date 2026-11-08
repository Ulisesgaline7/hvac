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
    protected static ?string $modelLabel = 'línea';

    protected static ?string $pluralModelLabel = 'líneas';

    protected static ?string $title = 'Equipos y fichas técnicas';

    private function prefijar(array $respuestas): array
    {
        $datos = [];
        foreach ($respuestas as $k => $v) {
            $datos['r_' . $k] = $v;
        }

        return $datos;
    }



    /** Fichas y tareas solo se editan con la OT en trabajo; en revisión, solo coordinador y administrador. */
    private function fichaEditable($record): bool
    {
        $estado = $record->ordenTrabajo?->estado;

        return in_array($estado, [\App\Enums\EstadoOt::Asignada, \App\Enums\EstadoOt::EnProceso, \App\Enums\EstadoOt::EnEspera], true)
            || ($estado === \App\Enums\EstadoOt::EnRevision && auth()->user()?->hasAnyRole(['administrador', 'coordinador']));
    }

    /** Actividades previstas de la línea: una por renglón de «tareas», con clave estable por texto. */
    private static function actividades($record): array
    {
        return \App\Support\ChecklistLinea::actividades($record);
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
                TextColumn::make('progreso')->label('Checklist')->placeholder('—')
                    ->state(function ($record) {
                        $a = self::actividades($record);
                        if (! $a) {
                            return null;
                        }

                        return count(array_intersect_key($record->datos_previstos['checklist'] ?? [], $a)) . '/' . count($a);
                    }),
                IconColumn::make('ficha.completa')->label('Ficha completa')->boolean(),
                TextColumn::make('docs_equipo')->label('Docs')->placeholder('—')
                    ->state(fn ($record) => $record->equipo_id
                        ? (\Spatie\MediaLibrary\MediaCollections\Models\Media::where('model_type', \App\Models\Equipo::class)
                            ->where('model_id', $record->equipo_id)->where('collection_name', 'documentos_equipo')->count() ?: null)
                        : null),
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
                Action::make('ver_equipo')
                    ->label('Ver equipo')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(fn ($record) => 'Equipo ' . ($record->equipo?->codigo ?? ''))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->modalContent(function ($record) {
                        $ot = $record->ordenTrabajo;
                        $listo = in_array($ot?->estado, [\App\Enums\EstadoOt::CompletadaTecnico, \App\Enums\EstadoOt::EnRevision, \App\Enums\EstadoOt::Cerrada], true);
                        $li = fn (string $url, string $texto) => '<li><a href="' . e($url) . '" target="_blank" rel="noopener" style="text-decoration:underline">' . e($texto) . '</a></li>';
                        $html = '<p style="margin-bottom:.5rem"><strong>' . e(trim(($record->equipo?->marca?->nombre ?? '') . ' ' . ($record->equipo?->modelo ?? ''))) . '</strong></p>';
                        if (! $listo) {
                            return new \Illuminate\Support\HtmlString($html . '<p>Los documentos se generan cuando la OT está completada.</p>');
                        }
                        $html .= '<ul style="list-style:disc;padding-left:1.2rem">';
                        foreach (['protocolo' => 'Protocolo de puesta en marcha', 'garantia' => 'Certificado de garantía', 'capacitacion' => 'Constancia de capacitación'] as $tipo => $nombre) {
                            $html .= $li(route('ordenes-trabajo.entregable', ['ot' => $ot->id, 'tipo' => $tipo, 'linea' => $record->id]), $nombre);
                        }
                        $html .= $li(route('ordenes-trabajo.entregable', ['ot' => $ot->id, 'tipo' => 'calendario']), 'Calendario de mantenimiento (propuesta)');
                        if ($ot->estado === \App\Enums\EstadoOt::Cerrada) {
                            $html .= $li(route('ordenes-trabajo.informe', $ot->id), 'Informe de la OT');
                        }
                        if ($record->equipo_id) {
                            $docs = \Spatie\MediaLibrary\MediaCollections\Models\Media::where('model_type', \App\Models\Equipo::class)
                                ->where('model_id', $record->equipo_id)->where('collection_name', 'documentos_equipo')->orderBy('id')->get();
                            foreach ($docs as $d) {
                                $html .= $li(route('equipos.documento', $d->id), $d->name . ' (documento del equipo)');
                            }
                        }

                        return new \Illuminate\Support\HtmlString($html . '</ul>');
                    }),
                Action::make('documentos_equipo')
                    ->label('Documentos del equipo')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->visible(fn ($record) => $record->equipo_id && \Spatie\MediaLibrary\MediaCollections\Models\Media::where('model_type', \App\Models\Equipo::class)
                        ->where('model_id', $record->equipo_id)->where('collection_name', 'documentos_equipo')->exists())
                    ->modalHeading(fn ($record) => 'Documentos de ' . ($record->equipo?->codigo ?? 'equipo'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->modalContent(function ($record) {
                        $docs = \Spatie\MediaLibrary\MediaCollections\Models\Media::where('model_type', \App\Models\Equipo::class)
                            ->where('model_id', $record->equipo_id)->where('collection_name', 'documentos_equipo')->orderBy('id')->get();
                        $tipos = \App\Filament\Resources\Equipos\RelationManagers\DocumentosEquipoRelationManager::TIPOS;
                        $html = '<ul style="list-style:disc;padding-left:1.2rem">';
                        foreach ($docs as $d) {
                            $html .= '<li><a href="' . e(route('equipos.documento', $d->id)) . '" target="_blank" rel="noopener" style="text-decoration:underline">'
                                . e($d->name) . '</a> <span style="opacity:.6">· ' . e($tipos[$d->getCustomProperty('tipo')] ?? 'Otro') . '</span></li>';
                        }
                        return new \Illuminate\Support\HtmlString($html . '</ul>');
                    }),
                Action::make('tareas')
                    ->label('Tareas previstas')
                    ->visible(fn ($record) => auth()->user()?->hasAnyRole(['administrador', 'coordinador'])
                        && ($this->fichaEditable($record) || ! in_array($record->ordenTrabajo?->estado,
                            [\App\Enums\EstadoOt::CompletadaTecnico, \App\Enums\EstadoOt::EnRevision, \App\Enums\EstadoOt::Cerrada], true)))
                    ->icon('heroicon-o-list-bullet')
                    ->fillForm(fn ($record) => ['tareas' => $record->datos_previstos['tareas'] ?? ''])
                    ->schema([
                        \Filament\Forms\Components\Select::make('plantilla')->label('Cargar lista sugerida')->dehydrated(false)->live()
                            ->options(fn () => collect(config('tareas.listas'))->map(fn ($l) => $l['nombre'])->all())
                            ->afterStateUpdated(function ($state, \Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get) {
                                if (! $state) {
                                    return;
                                }
                                $actual = trim((string) $get('tareas'));
                                $set('tareas', trim($actual . "\n" . implode("\n", config("tareas.listas.$state.tareas", []))));
                            }),
                        \Filament\Forms\Components\Textarea::make('tareas')->label('Tareas previstas')->rows(10)->helperText('Una actividad por línea; cada una será un ítem del checklist.'),
                    ])
                    ->action(function (array $data, $record) {
                        $record->update(['datos_previstos' => array_merge($record->datos_previstos ?? [], ['tareas' => $data['tareas']])]);
                        Notification::make()->success()->title('Tareas previstas guardadas')->send();
                    }),
                Action::make('checklist')
                    ->label('Checklist')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn ($record) => $this->fichaEditable($record) && self::actividades($record) !== [])
                    ->modalWidth('2xl')
                    ->fillForm(function ($record) {
                        $guardado = $record->datos_previstos['checklist'] ?? [];
                        $datos = [];
                        foreach (self::actividades($record) as $k => $texto) {
                            $datos["e_$k"] = $guardado[$k]['estado'] ?? null;
                            $datos["o_$k"] = $guardado[$k]['obs'] ?? null;
                        }

                        return $datos;
                    })
                    ->schema(function ($record) {
                        $campos = [];
                        foreach (self::actividades($record) as $k => $texto) {
                            $campos[] = \Filament\Forms\Components\ToggleButtons::make("e_$k")
                                ->label($texto)->inline()->live()
                                ->options(['ok' => 'OK', 'na' => 'N/A', 'falla' => 'Falla'])
                                ->colors(['ok' => 'success', 'na' => 'gray', 'falla' => 'danger']);
                            $campos[] = \Filament\Forms\Components\Textarea::make("o_$k")
                                ->label('Observación')->rows(2)
                                ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get("e_$k") === 'falla')
                                ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get("e_$k") === 'falla');
                        }

                        return $campos;
                    })
                    ->action(function (array $data, $record) {
                        $resultado = [];
                        foreach (self::actividades($record) as $k => $texto) {
                            if (! empty($data["e_$k"])) {
                                $resultado[$k] = ['texto' => $texto, 'estado' => $data["e_$k"], 'obs' => $data["o_$k"] ?? null];
                            }
                        }
                        $record->update(['datos_previstos' => array_merge($record->datos_previstos ?? [], ['checklist' => $resultado])]);
                        Notification::make()->success()->title('Checklist guardado')->send();
                    }),
                Action::make('movimiento')
                    ->label('Refrigerante')
                    ->icon('heroicon-o-beaker')
                    ->visible(fn ($record) => in_array($record->ordenTrabajo?->estado, [\App\Enums\EstadoOt::EnProceso, \App\Enums\EstadoOt::EnEspera], true)
                        && in_array(\App\Models\TipoEquipo::find($record->tipo_equipo_id ?: $record->equipo?->tipo_equipo_id)?->familia,
                            ['aire_acondicionado', 'cuartos_frios', 'refrigeracion_comercial'], true))
                    ->schema([
                        \Filament\Forms\Components\Select::make('cilindro_id')
                            ->label('Cilindro')
                            ->options(fn () => \App\Models\CilindroRefrigerante::with('refrigerante')
                                ->where('estado', 'activo')->get()
                                ->mapWithKeys(fn ($c) => [$c->id => "{$c->codigo} · {$c->refrigerante?->nombre} · saldo {$c->saldoKg()} kg"]))
                            ->searchable(),
                        \Filament\Forms\Components\Select::make('tipo')
                            ->options(fn () => $this->getOwnerRecord()->tipo === \App\Enums\TipoOt::Instalacion
                                ? ['carga' => 'Carga (agregado)']
                                : ['carga' => 'Carga (agregado)', 'recuperacion' => 'Recuperación'])
                            ->default(fn () => $this->getOwnerRecord()->tipo === \App\Enums\TipoOt::Instalacion ? 'carga' : null)
                            ->required(),
                        \Filament\Forms\Components\TextInput::make('cantidad_kg')
                            ->label('Cantidad')->numeric()->minValue(0.01)->suffix('kg')->required(),
                        \Filament\Forms\Components\Textarea::make('observaciones'),
                    ])
                    ->action(function (array $data, $record) {
                        if ($data['tipo'] === 'recuperacion' && $this->getOwnerRecord()->tipo === \App\Enums\TipoOt::Instalacion) {
                            Notification::make()->danger()->title('En una instalación solo se registra carga de refrigerante.')->send();

                            return;
                        }

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
                    ->visible(fn ($record) => $this->fichaEditable($record))
                    ->icon('heroicon-o-clipboard-document-list')
                    ->modalWidth('4xl')
                    ->fillForm(function ($record) {
                        try {
                            $svc = app(FichaService::class);

                            return $this->prefijar($svc->prellenar($record, $svc->obtenerOCrear($record)));
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

                        return FormularioDinamico::componentes($ficha->campos());
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
