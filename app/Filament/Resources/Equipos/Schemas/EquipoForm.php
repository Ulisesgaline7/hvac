<?php

namespace App\Filament\Resources\Equipos\Schemas;

use App\Enums\EstadoEquipo;
use App\Enums\TipoOt;
use App\Models\Cliente;
use App\Models\OrdenTrabajo;
use App\Models\Sucursal;
use App\Models\TipoEquipo;
use App\Services\AjustesPlantilla;
use App\Services\FormularioDinamico;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class EquipoForm
{
    public static function configure(Schema $schema): Schema
    {
        $esPropia = fn (Get $get): bool => $get('origen') === 'propia';

        return $schema
            ->components([
                Select::make('cliente_id')
                    ->label('Cliente')
                    ->options(fn () => Cliente::orderBy('razon_social')->pluck('razon_social', 'id'))
                    ->searchable()->live()->required()->dehydrated(false)
                    ->afterStateHydrated(function (Select $component, $record) {
                        if ($record) {
                            $component->state($record->area?->sucursal?->cliente_id);
                        }
                    })
                    ->afterStateUpdated(function (Set $set) {
                        $set('sucursal_id', null);
                        $set('area_id', null);
                    }),
                Select::make('sucursal_id')
                    ->label('Sucursal')
                    ->options(fn (Get $get) => $get('cliente_id')
                        ? Sucursal::where('cliente_id', $get('cliente_id'))->orderBy('nombre')->pluck('nombre', 'id')
                        : [])
                    ->searchable()->live()->required()->dehydrated(false)
                    ->disabled(fn (Get $get) => blank($get('cliente_id')))
                    ->afterStateHydrated(function (Select $component, $record) {
                        if ($record) {
                            $component->state($record->area?->sucursal_id);
                        }
                    })
                    ->afterStateUpdated(fn (Set $set) => $set('area_id', null)),
                Select::make('area_id')
                    ->label('Área')
                    ->relationship('area', 'nombre', fn ($query, Get $get) => $query->where('sucursal_id', $get('sucursal_id')))
                    ->searchable()->preload()->required()
                    ->disabled(fn (Get $get) => blank($get('sucursal_id'))),
                Select::make('tipo_equipo_id')
                    ->label('Tipo de equipo')->relationship('tipo', 'nombre', fn ($query) => $query->orderBy('familia')->orderBy('nombre'))
                    ->getOptionLabelFromRecordUsing(fn ($record) => ucfirst(str_replace('_', ' ', $record->familia)) . ' · ' . $record->nombre)
                    ->searchable()->preload()
                    ->live()->required(),
                Select::make('origen')
                    ->label('Origen')
                    ->options(['existente' => 'Ya estaba instalado', 'propia' => 'Instalado por nosotros'])
                    ->default('existente')->live()->required(),
                Select::make('orden_instalacion_id')
                    ->label('OT de instalación')
                    ->options(fn (Get $get) => OrdenTrabajo::where('tipo', TipoOt::Instalacion)
                        ->when($get('cliente_id'), fn ($q, $c) => $q->where('cliente_id', $c))
                        ->orderByDesc('id')->pluck('numero', 'id'))
                    ->searchable()
                    ->visible($esPropia)->required($esPropia),
                Select::make('marca_id')
                    ->label('Marca')->relationship('marca', 'nombre')->searchable()->preload(),
                TextInput::make('modelo'),
                TextInput::make('serie'),
                Select::make('refrigerante_id')
                    ->label('Refrigerante')->relationship('refrigerante', 'nombre')->searchable()->preload()
                    ->visible(fn (Get $get): bool => in_array(TipoEquipo::find($get('tipo_equipo_id'))?->familia, ['aire_acondicionado', 'cuartos_frios', 'refrigeracion_comercial'], true)),
                TextInput::make('voltaje'),
                TextInput::make('corriente'),
                Section::make('Datos técnicos')
                    ->schema(function (Get $get): array {
                        $tipo = TipoEquipo::find($get('tipo_equipo_id'));

                        $base = $tipo?->campos_registro ?? [];
                        if ($tipo && $get('cliente_id')) {
                            $base = app(AjustesPlantilla::class)->camposRegistro((int) $get('cliente_id'), $tipo->id, $base);
                        }
                        $comps = FormularioDinamico::componentes($base, 'datos_placa.');
                        $oblig = collect($base)->where('obligatorio', true)
                            ->map(fn ($c) => 'datos_placa.' . $c['clave'])->all();
                        foreach ($comps as $c) {
                            if (in_array($c->getName(), $oblig, true)) {
                                $c->required();
                            }
                        }

                        return $comps;
                    })
                    ->columns(2)->columnSpanFull()
                    ->visible(fn (Get $get): bool => filled($get('tipo_equipo_id'))),
                KeyValue::make('datos_placa.otros')->label('Otros datos de placa')->columnSpanFull(),
                DatePicker::make('fecha_instalacion')->required($esPropia),
                DatePicker::make('garantia_hasta')->required($esPropia),
                Select::make('estado')
                    ->options(EstadoEquipo::class)
                    ->default('operativo')
                    ->required(),
                \Filament\Forms\Components\FileUpload::make('foto_ruta')->label('Foto del equipo')
                    ->image()->disk('public')->directory('equipos')->visibility('public')->maxSize(4096)->columnSpanFull(),
                Textarea::make('notas')->columnSpanFull(),
            ]);
    }
}
