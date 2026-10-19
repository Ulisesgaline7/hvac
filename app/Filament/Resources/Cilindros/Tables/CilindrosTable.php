<?php

namespace App\Filament\Resources\Cilindros\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CilindrosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo')->label('Código')->searchable()->sortable(),
                TextColumn::make('refrigerante.nombre')->label('Refrigerante'),
                TextColumn::make('peso_inicial_kg')->label('Inicial (kg)'),
                TextColumn::make('saldo')->label('Saldo (kg)')->state(fn ($record) => $record->saldoKg()),
                TextColumn::make('estado')->badge(),
            ])
            ->recordActions([EditAction::make()]);
    }
}
