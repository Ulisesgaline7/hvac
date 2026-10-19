<?php

namespace App\Filament\Resources\Facturas\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FacturaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->disabled()->components([
            Section::make('Factura')->columns(3)->schema([
                TextInput::make('numero')->label('Número'),
                TextInput::make('estado'),
                TextInput::make('cliente.razon_social')->label('Cliente'),
                DatePicker::make('fecha'),
                DatePicker::make('vence')->label('Vence'),
                TextInput::make('orden_trabajo_id')->label('OT (id)'),
                TextInput::make('contrato_id')->label('Contrato (id)'),
                DatePicker::make('periodo')->label('Periodo desde'),
            ]),
            Section::make('Datos fiscales')->columns(3)
                ->description('Se llenan cuando exista autorización de numeración (CAI) del SAR.')
                ->schema([
                    TextInput::make('cai')->label('CAI'),
                    TextInput::make('rango_inicial')->label('Rango inicial'),
                    TextInput::make('rango_final')->label('Rango final'),
                    DatePicker::make('fecha_limite_emision')->label('Fecha límite de emisión'),
                    TextInput::make('establecimiento'),
                    TextInput::make('punto_emision')->label('Punto de emisión'),
                    TextInput::make('tipo_documento')->label('Tipo de documento'),
                    TextInput::make('rtn_cliente')->label('RTN del cliente'),
                ]),
            Section::make('Montos')->columns(4)->schema([
                TextInput::make('subtotal')->prefix('L'),
                TextInput::make('isv')->label('ISV')->prefix('L'),
                TextInput::make('total')->prefix('L'),
                TextInput::make('pagado')->prefix('L'),
            ]),
        ]);
    }
}
