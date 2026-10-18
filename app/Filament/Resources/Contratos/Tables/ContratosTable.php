<?php

namespace App\Filament\Resources\Contratos\Tables;

use App\Enums\EstadoContrato;
use App\Enums\TipoContrato;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ContratosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('vigencia_fin')
            ->columns([
                TextColumn::make('cliente.razon_social')->label('Cliente')->searchable()->sortable(),
                TextColumn::make('tipo')->badge(),
                TextColumn::make('estado')->badge(),
                TextColumn::make('vigencia_inicio')->label('Inicio')->date()->sortable(),
                TextColumn::make('vigencia_fin')
                    ->label('Vence')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->estado === EstadoContrato::Activo && $record->vigencia_fin->lt(now()->addDays(30)) ? 'warning' : null),
                TextColumn::make('lineas_count')->counts('lineas')->label('Equipos'),
            ])
            ->filters([
                SelectFilter::make('estado')->options(EstadoContrato::class),
                SelectFilter::make('tipo')->options(TipoContrato::class),
                TrashedFilter::make(),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
