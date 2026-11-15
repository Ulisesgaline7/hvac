<?php

namespace App\Filament\Resources\Colaboradores\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VinculosRelationManager extends RelationManager
{
    protected static string $relationship = 'vinculos';

    protected static ?string $title = 'Vínculos (historial)';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('cargo')
            ->columns([
                TextColumn::make('tipo')->badge()
                    ->formatStateUsing(fn ($state) => ucfirst((string) $state)),
                TextColumn::make('cargo'),
                TextColumn::make('fecha_inicio')->label('Desde')->date(),
                TextColumn::make('fecha_fin')->label('Hasta')->date()->placeholder('Vigente'),
                TextColumn::make('motivo_fin')->label('Motivo de fin'),
            ]);
    }
}
