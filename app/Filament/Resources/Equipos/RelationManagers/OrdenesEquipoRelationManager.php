<?php

namespace App\Filament\Resources\Equipos\RelationManagers;

use App\Filament\Resources\OrdenTrabajos\OrdenTrabajoResource;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrdenesEquipoRelationManager extends RelationManager
{
    protected static string $relationship = 'lineas';
    protected static ?string $title = 'Órdenes de trabajo';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('ordenTrabajo.numero')->label('OT'),
                TextColumn::make('ordenTrabajo.tipo')->label('Tipo')->badge(),
                TextColumn::make('ordenTrabajo.estado')->label('Estado')->badge(),
                TextColumn::make('ordenTrabajo.fecha_programada')->label('Fecha')->date('d/m/Y')->placeholder('—'),
                IconColumn::make('ficha.completa')->label('Ficha completa')->boolean(),
            ])
            ->recordActions([
                Action::make('abrir')->label('Abrir OT')->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn ($record) => OrdenTrabajoResource::getUrl('edit', ['record' => $record->orden_trabajo_id])),
            ]);
    }
}
