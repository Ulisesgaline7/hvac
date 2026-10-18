<?php

namespace App\Filament\Resources\OrdenTrabajos\Schemas;

use App\Enums\EstadoOt;
use App\Enums\TipoOt;
use App\Models\Contrato;
use App\Models\Sucursal;
use App\Models\Tecnico;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;

class OrdenTrabajoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
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
                    ->options([TipoOt::Correctivo->value => 'Correctivo', TipoOt::Instalacion->value => 'Instalación'])
                    ->required()
                    ->live()
                    ->disabledOn('edit')
                    ->helperText('Las preventivas las genera el contrato.')
                    ->afterStateHydrated(function (Select $component, $state) {
                        if ($state === TipoOt::Preventivo || $state === TipoOt::Preventivo->value) {
                            $component->options(TipoOt::class);
                        }
                    }),
                Select::make('prioridad')
                    ->options(['baja' => 'Baja', 'normal' => 'Normal', 'alta' => 'Alta', 'emergencia' => 'Emergencia'])
                    ->default('normal')
                    ->required(),
                Select::make('cliente_id')
                    ->label('Cliente')
                    ->relationship('cliente', 'razon_social')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
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
                    ->afterStateUpdated(fn (Set $set) => $set('equipos', [])),
                Select::make('contrato_id')
                    ->label('Contrato')
                    ->options(fn (Get $get) => Contrato::where('cliente_id', $get('cliente_id'))->where('estado', 'activo')->get()
                        ->mapWithKeys(fn ($c) => [$c->id => "#{$c->id} · {$c->tipo->getLabel()} · vence {$c->vigencia_fin->format('d/m/Y')}"])),
                Select::make('equipos')
                    ->label('Equipos')
                    ->multiple()
                    ->relationship('equipos', 'codigo', fn ($query, Get $get) => $query->whereIn(
                        'area_id',
                        DB::table('areas')->where('sucursal_id', $get('sucursal_id') ?: 0)->select('id')
                    ))
                    ->preload()
                    ->visible(fn (Get $get) => $get('tipo') !== TipoOt::Instalacion->value)
                    ->columnSpanFull(),
            ]),
            Section::make('Aviso y programación')->columns(2)->schema([
                Select::make('origen_aviso')
                    ->label('Origen del aviso')
                    ->options(['llamada' => 'Llamada', 'mensaje' => 'Mensaje', 'correo' => 'Correo', 'hallazgo' => 'Hallazgo en visita preventiva']),
                TextInput::make('contacto_aviso')->label('Contacto que reportó'),
                Textarea::make('descripcion')->label('Descripción')->columnSpanFull(),
                Select::make('tecnico_id')
                    ->label('Técnico')
                    ->options(fn () => Tecnico::where('estado_homologacion', 'aprobado')->with('user')->get()->pluck('user.name', 'id'))
                    ->searchable()
                    ->helperText('Solo técnicos aprobados.'),
                DatePicker::make('fecha_programada')->label('Fecha programada'),
            ]),
            Section::make('Ejecución')->visibleOn('edit')->columns(1)->schema([
                Textarea::make('diagnostico')->label('Diagnóstico'),
                Textarea::make('causa_raiz')->label('Causa raíz'),
                Textarea::make('reparacion')->label('Reparación realizada'),
                Textarea::make('motivo_devolucion')->label('Motivo de devolución')->disabled(),
                Textarea::make('motivo_reprogramacion')->label('Motivo de reprogramación')->disabled(),
            ]),
        ]);
    }
}
