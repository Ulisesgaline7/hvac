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
            Section::make('Montos')->columns(4)->schema([
                TextInput::make('subtotal')->prefix('L'),
                TextInput::make('isv')->label('ISV')->prefix('L'),
                TextInput::make('total')->prefix('L'),
                TextInput::make('pagado')->prefix('L'),
            ]),
        ]);
    }
}
