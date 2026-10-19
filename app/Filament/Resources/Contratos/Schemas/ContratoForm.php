<?php

namespace App\Filament\Resources\Contratos\Schemas;

use App\Enums\EstadoContrato;
use App\Enums\TipoContrato;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ContratoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('cliente_id')
                ->label('Cliente')
                ->relationship('cliente', 'razon_social')
                ->searchable()
                ->preload()
                ->required(),
            Select::make('tipo')->options(TipoContrato::class)->required(),
            DatePicker::make('vigencia_inicio')->label('Inicio de vigencia')->required(),
            DatePicker::make('vigencia_fin')->label('Fin de vigencia')->required()->afterOrEqual('vigencia_inicio'),
            Select::make('estado')
                ->options(EstadoContrato::class)
                ->default('borrador')
                ->disabled()
                ->dehydrated(false)
                ->helperText('El estado cambia con las acciones Activar, Cancelar o Renovar.'),
            TextInput::make('anticipacion_dias')
                ->label('Anticipación de OT (días)')
                ->required()
                ->numeric()
                ->minValue(0)
                ->default(30),
            TextInput::make('cuota_monto')
                ->label('Cuota por periodo (L, antes de ISV)')
                ->numeric()->prefix('L')->minValue(0)
                ->helperText('Se factura sola al inicio de cada periodo con el contrato activo. Vacío = no factura.'),
            Select::make('periodicidad')
                ->label('Periodicidad de cobro')
                ->options(['mensual' => 'Mensual', 'trimestral' => 'Trimestral', 'semestral' => 'Semestral', 'anual' => 'Anual'])
                ->default(config('cobros.contrato_periodicidad')),
            KeyValue::make('sla')->label('SLA')->keyLabel('Concepto')->valueLabel('Valor')->columnSpanFull(),
            Textarea::make('servicios_incluidos')->columnSpanFull(),
            Textarea::make('exclusiones')->columnSpanFull(),
            Textarea::make('garantias')->columnSpanFull(),
            Textarea::make('notas')->columnSpanFull(),
        ]);
    }
}
