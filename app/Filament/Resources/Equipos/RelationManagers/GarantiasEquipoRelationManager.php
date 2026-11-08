<?php

namespace App\Filament\Resources\Equipos\RelationManagers;

use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GarantiasEquipoRelationManager extends RelationManager
{
    protected static string $relationship = 'garantias';
    protected static ?string $title = 'Garantías';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('numero')
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('numero')->label('Número'),
                TextColumn::make('categoria')->label('Categoría')->badge(),
                TextColumn::make('estado')->label('Estado')->badge()
                    ->state(fn ($record) => $record->estado === 'anulada' ? 'Anulada'
                        : ($record->hasta->isPast() ? 'Vencida' : 'Vigente'))
                    ->color(fn (string $state) => match ($state) {
                        'Vigente' => 'success', 'Vencida' => 'warning', default => 'danger',
                    }),
                TextColumn::make('dias')->label('Días'),
                TextColumn::make('desde')->label('Desde')->date('d/m/Y'),
                TextColumn::make('hasta')->label('Hasta')->date('d/m/Y'),
                TextColumn::make('ordenTrabajo.numero')->label('OT'),
            ])
            ->recordActions([
                Action::make('pdf')->label('Ver PDF')->icon('heroicon-o-document-arrow-down')
                    ->url(fn ($record) => route('ordenes-trabajo.entregable', [$record->orden_trabajo_id, 'garantia']), shouldOpenInNewTab: true),
            ]);
    }
}
