<?php

namespace App\Filament\Resources\OrdenTrabajos\RelationManagers;

use App\Enums\EstadoOt;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HistorialRelationManager extends RelationManager
{
    protected static string $relationship = 'historial';

    protected static ?string $title = 'Historial de estados';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('estado_nuevo')
            ->columns([
                TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i'),
                TextColumn::make('estado_anterior')->label('De')->badge()
                    ->formatStateUsing(fn ($state) => $state ? EstadoOt::from($state)->getLabel() : '—'),
                TextColumn::make('estado_nuevo')->label('A')->badge()
                    ->formatStateUsing(fn ($state) => EstadoOt::from($state)->getLabel()),
                TextColumn::make('usuario.name')->label('Usuario')->placeholder('Sistema'),
                TextColumn::make('motivo')->wrap(),
            ])
            ->paginated(false);
    }
}
