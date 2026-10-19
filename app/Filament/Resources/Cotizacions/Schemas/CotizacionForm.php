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
