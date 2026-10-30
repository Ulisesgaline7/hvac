<?php

namespace App\Filament\Resources\Insumos\RelationManagers;

use App\Filament\Resources\MovimientosInventario\MovimientoInventarioResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class MovimientosRelationManager extends RelationManager
{
    protected static string $relationship = 'movimientos';

    protected static ?string $title = 'Movimientos';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->modifyQueryUsing(fn ($query) => $query->with(['user', 'ordenTrabajo']))
            ->defaultSort('created_at', 'desc')
            ->columns(MovimientoInventarioResource::columnas(conInsumo: false));
    }
}
