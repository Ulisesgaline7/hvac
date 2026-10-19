<?php

namespace App\Filament\Resources\OrdenTrabajos\Schemas;

use App\Enums\EstadoOt;
use App\Enums\TipoOt;
use App\Models\Contrato;
use App\Models\Sucursal;
use App\Models\Tecnico;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;

class OrdenTrabajoForm
{
    public static function configure(Schema $schema): Schema
    {
        // ¿El tipo elegido está en la lista? (sin tipo elegido se muestra todo)
        $es = fn (Get $get, array $tipos): bool => in_array($get('tipo'), $tipos, true);
        $sinTipo = fn (Get $get): bool => blank($get('tipo'));

        return $schema->disabled(fn ($record) => $record && in_array($record->estado, [EstadoOt::Cerrada, EstadoOt::Cancelada], true))->components([
            Section::make('Datos generales')->columns(2)->schema([
                TextInput::make('numero')
                    ->label('Número')
                    ->disabled()
                    ->dehydrated(false)
                    ->placeholder('Se asigna al guardar')
                    ->visibleOn('edit'),
                Select::make('estado')
                    ->options(EstadoOt::class)
                    ->disabled()
                    ->dehydrated(false)
                    ->visibleOn('edit')
                    ->helperText('Se cambia con la acción «Cambiar estado».'),
                Select::make('tipo')
                    ->options([
                        TipoOt::VisitaTecnica->value => 'Visita técnica (para cotizar)',
                        TipoOt::Correctivo->value => 'Correctivo',
                        TipoOt::Instalacion->value => 'Instalación',
                        TipoOt::Emergencia->value => 'Emergencia',
                    ])
                    ->required()
                    ->live()
                    ->disabledOn('edit')
                    ->helperText('Las preventivas las genera el contrato. El formulario cambia según el tipo.')
                    ->afterStateUpdated(function ($state, Set $set) {
                        if ($state === TipoOt::Emergencia->value) {
                            $set('prioridad', 'emergencia');
                        }
                        if ($state === TipoOt::Instalacion->value) {
                            $set('contrato_id', null);
                            $set('equipos', []);
                        }
                        if ($state === TipoOt::VisitaTecnica->value) {
                            $set('contrato_id', null);
                        }
                    })
                    ->afterStateHydrated(function (Select $component, $state) {
                        if ($state === TipoOt::Preventivo || $state === TipoOt::Preventivo->value) {
                            $component->options(TipoOt::class);
                        }
                    }),
                Select::make('prioridad')
                    ->options(['baja' => 'Baja', 'normal' => 'Normal', 'alta' => 'Urgente', 'emergencia' => 'Emergencia'])
                    ->default('normal')
                    ->required(),
                Select::make('cliente_id')
                    ->label('Cliente')
                    ->relationship('cliente', 'razon_social')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->createOptionForm([
                        TextInput::make('razon_social')->label('Razón social / nombre')->required()->maxLength(255),
                        TextInput::make('identificacion_fiscal')->label('RTN / identificación fiscal')->maxLength(50),
                    ])
                    ->createOptionModalHeading('Nuevo cliente')
                    ->afterStateUpdated(function (Set $set) {
                        $set('sucursal_id', null);
                        $set('contrato_id', null);
                        $set('equipos', []);
                    }),
                Select::make('sucursal_id')
                    ->label('Sucursal')
                    ->options(fn (Get $get) => Sucursal::where('cliente_id', $get('cliente_id'))->orderBy('nombre')->pluck('nombre', 'id'))
                    ->required()
                    ->live()
                    ->disabled(fn (Get $get) => ! $get('cliente_id'))
                    ->helperText(fn (Get $get) => $get('cliente_id') ? null : 'Elija primero el cliente.')
                    ->createOptionForm([
                        TextInput::make('nombre')->required()->maxLength(255),
                        TextInput::make('direccion')->label('Dirección')->maxLength(255),
                    ])
                    ->createOptionModalHeading('Nueva sucursal')
                    ->createOptionUsing(fn (array $data, Get $get) => Sucursal::create($data + ['cliente_id' => $get('cliente_id')])->getKey())
                    ->afterStateUpdated(fn (Set $set) => $set('equipos', [])),
                Select::make('contrato_id')
                    ->label('Contrato')
                    ->options(fn (Get $get) => Contrato::where('cliente_id', $get('cliente_id'))->where('estado', 'activo')->get()
                        ->mapWithKeys(fn ($c) => [$c->id => "#{$c->id} · {$c->tipo->getLabel()} · vence {$c->vigencia_fin->format('d/m/Y')}"]))
                    ->visible(fn (Get $get) => $sinTipo($get) || $es($get, ['correctivo', 'emergencia', 'preventivo'])),
                Select::make('equipos')
                    ->label(fn (Get $get) => $es($get, ['visita_tecnica']) ? 'Equipos a revisar (opcional)' : 'Equipos')
                    ->multiple()
                    ->relationship('equipos', 'codigo', fn ($query, Get $get) => $query->whereIn(
                        'equipos.area_id',
                        DB::table('areas')->where('sucursal_id', $get('sucursal_id') ?: 0)->select('id')
                    ))
                    ->disabled(fn (Get $get) => ! $get('sucursal_id'))
                    ->preload()
                    ->visible(fn (Get $get) => ! $es($get, ['instalacion']))
                    ->columnSpanFull(),
            ]),
            Section::make('Aviso y programación')->columns(2)->schema([
                Select::make('origen_aviso')
                    ->label('Origen del aviso')
                    ->options(['llamada' => 'Llamada', 'mensaje' => 'Mensaje', 'correo' => 'Correo', 'hallazgo' => 'Hallazgo en visita preventiva'])
                    ->visible(fn (Get $get) => ! $es($get, ['instalacion'])),
                TextInput::make('contacto_aviso')->label('Contacto que reportó')
                    ->visible(fn (Get $get) => ! $es($get, ['instalacion'])),
                Textarea::make('descripcion')
                    ->label(fn (Get $get) => match ($get('tipo')) {
                        'visita_tecnica' => 'Qué hay que revisar o cotizar',
                        'instalacion' => 'Alcance de la instalación',
                        'emergencia' => 'Falla reportada',
                        default => 'Descripción',
                    })
                    ->required(fn (Get $get) => $es($get, ['correctivo', 'emergencia', 'visita_tecnica']))
                    ->columnSpanFull(),
                Select::make('tecnico_id')
                    ->label('Técnico')
                    ->options(fn () => Tecnico::where('estado_homologacion', 'aprobado')->with('user')->get()->pluck('user.name', 'id'))
                    ->searchable()
                    ->helperText('Solo técnicos aprobados.'),
                DatePicker::make('fecha_programada')->label('Fecha programada'),
                TimePicker::make('hora_programada')->label('Hora programada')->seconds(false),
            ]),
            Section::make('Cobro de la visita')->columns(2)
                ->description('Por defecto se aplica la regla de config/visita.php: correctivo aprobado bonifica, los demás cobran.')
                ->visible(fn (Get $get) => $es($get, ['visita_tecnica']))
                ->schema([
                    Select::make('visita_cobro')
                        ->label('Tratamiento')
                        ->options(['gratis' => 'Sin costo (excepción)', 'cobrar' => 'Cobrar siempre'])
                        ->placeholder('Según la regla'),
                    TextInput::make('visita_monto')->label('Monto (L)')->numeric()->prefix('L')->minValue(0)
                        ->placeholder(fn () => number_format((float) config('visita.costo'), 2))
                        ->helperText('Vacío = tarifa estándar.'),
                ]),
            Section::make('Alcance y costo')->columns(2)
                ->visible(fn (Get $get) => $sinTipo($get) || $es($get, ['instalacion', 'correctivo', 'emergencia']))
                ->schema([
                    Textarea::make('materiales_autorizados')->label('Materiales y repuestos autorizados')->columnSpanFull(),
                    TextInput::make('precio_estimado')->label('Precio (L)')->numeric()->prefix('L')->minValue(0)
                        ->helperText('Si viene de una cotización aprobada, se llena desde ella.'),
                    TextInput::make('anticipo')->label('Anticipo (L)')->numeric()->prefix('L')->minValue(0)
                        ->visible(fn (Get $get) => ! $es($get, ['emergencia'])),
                    TextInput::make('anticipo_pct')->label('Anticipo (%)')->numeric()->suffix('%')->minValue(0)->maxValue(100)
                        ->placeholder('Según configuración')
                        ->visible(fn (Get $get) => ! $es($get, ['emergencia'])),
                    Select::make('dias_credito')->label('Crédito')
                        ->options([0 => 'Contra entrega', 15 => '15 días', 20 => '20 días', 25 => '25 días', 30 => '30 días'])
                        ->placeholder('Según configuración')
                        ->helperText('Se negocia por trabajo. Si hay cotización aprobada, hereda el suyo.'),
                ]),
            Section::make('Seguridad antes de iniciar')->schema([
                CheckboxList::make('seguridad_previa')->hiddenLabel()->options([
                    'epp_listo' => 'Equipo de protección listo',
                    'energia_bloqueada' => 'Energía bloqueada antes de intervenir',
                    'altura_evaluada' => 'Trabajo en altura evaluado',
                ])->columns(1),
            ]),
            Section::make('Ejecución')->visibleOn('edit')->columns(1)->schema([
                Textarea::make('diagnostico')->label('Diagnóstico'),
                Textarea::make('recomendaciones')->label('Recomendaciones (equipo, materiales, alcance)')
                    ->visible(fn (Get $get) => $es($get, ['visita_tecnica'])),
                Textarea::make('causa_raiz')->label('Causa raíz')
                    ->visible(fn (Get $get) => ! $es($get, ['visita_tecnica', 'instalacion'])),
                Textarea::make('reparacion')->label('Reparación realizada')
                    ->visible(fn (Get $get) => ! $es($get, ['visita_tecnica', 'instalacion'])),
                Textarea::make('motivo_devolucion')->label('Motivo de devolución')->disabled(),
                Textarea::make('motivo_reprogramacion')->label('Motivo de reprogramación')->disabled(),
            ]),
        ]);
    }
}
