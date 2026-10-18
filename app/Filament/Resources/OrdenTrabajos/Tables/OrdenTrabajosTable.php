<?php

namespace App\Filament\Resources\OrdenTrabajos\Tables;

use App\Enums\EstadoOt;
use App\Enums\TipoOt;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class OrdenTrabajosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('fecha_programada')
            ->columns([
                TextColumn::make('numero')->label('OT')->searchable()->sortable(),
                TextColumn::make('tipo')->badge(),
                TextColumn::make('estado')->badge(),
                TextColumn::make('prioridad')->badge()->color(fn (string $state) => match ($state) {
                    'emergencia' => 'danger',
                    'alta' => 'warning',
                    default => 'gray',
                }),
                TextColumn::make('cliente.razon_social')->label('Cliente')->searchable(),
                TextColumn::make('sucursal.nombre')->label('Sucursal'),
                TextColumn::make('tecnico.user.name')->label('Técnico')->placeholder('Sin asignar'),
                TextColumn::make('fecha_programada')->label('Programada')->date()->sortable(),
                IconColumn::make('tardia')->label('Tardía')->boolean()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('estado')->options(EstadoOt::class)->multiple(),
                SelectFilter::make('tipo')->options(TipoOt::class),
                SelectFilter::make('tecnico_id')->label('Técnico')->relationship('tecnico.user', 'name'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('informe')
                    ->label('Informe')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn ($record) => route('ordenes-trabajo.informe', $record->id), shouldOpenInNewTab: true),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
