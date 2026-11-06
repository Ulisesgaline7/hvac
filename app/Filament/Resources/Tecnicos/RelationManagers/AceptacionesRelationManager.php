<?php

namespace App\Filament\Resources\Tecnicos\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AceptacionesRelationManager extends RelationManager
{
    protected static string $relationship = 'aceptaciones';
    protected static ?string $modelLabel = 'aceptación';

    protected static ?string $pluralModelLabel = 'aceptaciones';

    protected static ?string $title = 'Acuerdos firmados';

    /** Solo lectura: una aceptación firmada no se crea, edita ni borra desde el admin. */
    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('firmado_nombre')
            ->defaultSort('aceptado_at', 'desc')
            ->columns([
                TextColumn::make('documento.nombre')->label('Documento'),
                TextColumn::make('version')->label('Versión')->placeholder('—'),
                TextColumn::make('firmado_nombre')->label('Firmó'),
                TextColumn::make('aceptado_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('ip')->label('IP')->placeholder('—'),
                TextColumn::make('hash_texto')->label('Huella del texto')
                    ->formatStateUsing(fn ($state) => substr((string) $state, 0, 12) . '…')
                    ->tooltip(fn ($record) => $record->hash_texto),
            ]);
    }
}
