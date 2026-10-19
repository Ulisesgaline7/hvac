<?php

namespace App\Filament\Resources\Facturas;

use App\Filament\Resources\Facturas\Pages\ListFacturas;
use App\Filament\Resources\Facturas\Pages\ViewFactura;
use App\Filament\Resources\Facturas\Schemas\FacturaForm;
use App\Filament\Resources\Facturas\Tables\FacturasTable;
use App\Models\Factura;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FacturaResource extends Resource
{
    protected static ?string $model = Factura::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $slug = 'facturas';

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['administrador', 'coordinador']);
    }

    public static function canCreate(): bool
    {
        return false; // se generan al cerrar la OT
    }

    public static function form(Schema $schema): Schema
    {
        return FacturaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FacturasTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFacturas::route('/'),
            'view' => ViewFactura::route('/{record}'),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'Factura';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Facturas y cobros';
    }
}
