<?php

namespace App\Filament\Resources\Cotizacions\Schemas;

use App\Models\ServicioCatalogo;
use App\Models\Sucursal;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class CotizacionForm
{
    public static function configure(Schema $schema): Schema
    {
        $bloqueada = fn ($record) => filled($record) && $record->estado !== 'borrador';

        return $schema->components([
            Hidden::make('vendedor_id')->default(fn () => auth()->id()),

            Select::make('cliente_id')->label('Cliente')
                ->relationship('cliente', 'razon_social')->searchable()->preload()->required()
                ->live()->afterStateUpdated(fn (Set $set) => $set('sucursal_id', null))
                ->disabled($bloqueada),
            Select::make('sucursal_id')->label('Sucursal')->required()->searchable()
                ->options(fn (Get $get) => $get('cliente_id')
                    ? Sucursal::where('cliente_id', $get('cliente_id'))->orderBy('nombre')->pluck('nombre', 'id')->all()
                    : [])
                ->disabled($bloqueada),
            DatePicker::make('fecha')->label('Fecha')->default(now())->required()->disabled($bloqueada),
            DatePicker::make('valida_hasta')->label('Válida hasta')
                ->default(fn () => now()->addWeekdays((int) config('tarifario.validez_dias_habiles')))
                ->disabled($bloqueada),
            TextInput::make('anticipo_pct')->label('Anticipo (%)')->numeric()->suffix('%')->minValue(0)->maxValue(100)
                ->placeholder('Según configuración')
                ->helperText('Porcentaje de anticipo negociado para este trabajo.')
                ->disabled($bloqueada),
            Select::make('dias_credito')->label('Crédito')
                ->options([0 => 'Contra entrega', 15 => '15 días', 20 => '20 días', 25 => '25 días', 30 => '30 días'])
                ->placeholder('Según configuración')
                ->helperText('De 15 a 30 días, negociado por trabajo.')
                ->disabled($bloqueada),

            TextInput::make('estado')->label('Estado')->disabled()->dehydrated(false)->visibleOn('edit'),

            Select::make('equipos')->label('Equipos a atender')->columnSpanFull()
                ->relationship(
                    'equipos',
                    'codigo',
                    modifyQueryUsing: fn ($query, Get $get) => $query->whereIn(
                        'equipos.area_id',
                        \App\Models\Area::where('sucursal_id', $get('sucursal_id') ?: 0)->select('id')
                    ),
                )
                ->getOptionLabelFromRecordUsing(fn ($r) => trim("{$r->codigo} · " . ($r->tipo?->nombre ?? '') . ' ' . ($r->modelo ?? '')))
                ->multiple()->searchable(['codigo', 'modelo', 'serie'])->preload()
                ->helperText('Elija la sucursal primero. Si el equipo no existe, regístrelo aquí mismo: se crea con su código y QR.')
                ->createOptionForm(fn (Get $get) => [
                    Select::make('area_id')->label('Área')->required()
                        ->options(\App\Models\Area::where('sucursal_id', $get('sucursal_id') ?: 0)->orderBy('nombre')->pluck('nombre', 'id')->all()),
                    Select::make('tipo_equipo_id')->label('Tipo de equipo')->required()
                        ->options(\App\Models\TipoEquipo::orderBy('nombre')->pluck('nombre', 'id')->all())->searchable(),
                    Select::make('marca_id')->label('Marca')
                        ->options(\App\Models\Marca::orderBy('nombre')->pluck('nombre', 'id')->all())->searchable(),
                    TextInput::make('modelo'),
                    TextInput::make('serie'),
                ])
                ->disabled($bloqueada),

            Repeater::make('lineas')->label('Líneas')->relationship('lineas')->columnSpanFull()
                ->defaultItems(1)->addActionLabel('Agregar línea')->columns(4)
                ->disabled($bloqueada)
                ->schema([
                    Select::make('tipo')->options(['servicio' => 'Servicio', 'repuesto' => 'Repuesto o material'])
                        ->default('servicio')->required(),
                    Select::make('servicio_id')->label('Servicio del tarifario')->searchable()->live()->columnSpan(3)
                        ->options(fn () => ServicioCatalogo::where('activo', true)->orderBy('codigo')->get()
                            ->mapWithKeys(fn ($s) => [$s->id => "{$s->codigo} · {$s->nombre}"])->all())
                        ->afterStateUpdated(function ($state, Set $set) {
                            $s = $state ? ServicioCatalogo::find($state) : null;
                            if (! $s) {
                                return;
                            }
                            $set('codigo', $s->codigo);
                            $set('descripcion', $s->nombre . ($s->detalle ? ' – ' . $s->detalle : ''));
                            $set('precio_unitario', $s->precio);
                            $set('cuenta_equipo', (bool) $s->cuenta_equipo);
                        }),
                    TextInput::make('descripcion')->label('Descripción')->required()->columnSpan(2),
                    TextInput::make('cantidad')->numeric()->minValue(0.01)->default(1)->required(),
                    TextInput::make('unidad')->default('unidad'),
                    TextInput::make('precio_unitario')->label('Precio unitario (L, sin ISV)')->numeric()->minValue(0)->required(),
                    Hidden::make('codigo'),
                    Hidden::make('cuenta_equipo')->default(false),
                ]),

            CheckboxList::make('recargos')->label('Recargos por condiciones especiales')->columns(2)->columnSpanFull()
                ->options(collect(config('tarifario.recargos'))->map(fn ($r) => $r['nombre'])->all())
                ->helperText('Se aplica el porcentaje mínimo de cada recargo (tarifario, sección 7).')
                ->disabled($bloqueada),
            Select::make('descuento_contrato')->label('Descuento por contrato anual')->placeholder('Sin contrato')
                ->options(collect(config('tarifario.descuentos'))->filter(fn ($d) => ! isset($d['desde']))->map(fn ($d) => $d['nombre'])->all())
                ->helperText('El descuento por volumen se calcula solo. No se acumulan: se aplica el mayor.')
                ->disabled($bloqueada),

            TextInput::make('visitas_anuales')->label('Visitas al año (solo planes)')->numeric()->integer()->minValue(1)->maxValue(24)
                ->helperText('Si se indica, el PDF muestra también el total anual.')
                ->disabled($bloqueada),
            TextInput::make('logistica.km')->label('Kilómetros adicionales de la ruta')->numeric()->minValue(0)->suffix('km')
                ->helperText('Recorrido adicional que genera la ruta, no un viaje completo por sede.')
                ->visible(fn () => (bool) config('tarifario.viaticos_detallados'))->disabled($bloqueada),
            TextInput::make('logistica.tecnicos')->label('Técnicos que viajan')->numeric()->integer()->minValue(0)
                ->visible(fn () => (bool) config('tarifario.viaticos_detallados'))->disabled($bloqueada),
            TextInput::make('logistica.dias')->label('Días de trabajo fuera de la zona')->numeric()->integer()->minValue(0)
                ->visible(fn () => (bool) config('tarifario.viaticos_detallados'))->disabled($bloqueada),
            TextInput::make('logistica.noches')->label('Noches de hospedaje')->numeric()->integer()->minValue(0)
                ->helperText('Déjelo en 0 si regresan el mismo día.')
                ->visible(fn () => (bool) config('tarifario.viaticos_detallados'))->disabled($bloqueada),
            TextInput::make('logistica.peajes')->label('Peajes y estacionamiento')->numeric()->minValue(0)->prefix('L')
                ->visible(fn () => (bool) config('tarifario.viaticos_detallados'))->disabled($bloqueada),

            TextInput::make('subtotal')->label('Subtotal')->prefix('L')->disabled()->dehydrated(false)->visibleOn('edit'),
            TextInput::make('isv')->label('ISV 15 %')->prefix('L')->disabled()->dehydrated(false)->visibleOn('edit'),
            TextInput::make('total')->label('Total')->prefix('L')->disabled()->dehydrated(false)->visibleOn('edit'),

            Textarea::make('condiciones')->label('Condiciones')->rows(8)->columnSpanFull()
                ->default(fn () => implode("\n", config('tarifario.condiciones')))
                ->disabled($bloqueada),
            Textarea::make('notas')->label('Notas internas')->columnSpanFull(),
        ]);
    }
}
