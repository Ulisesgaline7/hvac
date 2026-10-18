<?php

namespace App\Filament\Resources\Equipos\Tables;

use Filament\Actions\BulkAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class EquiposTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo')->label('Código')
                    ->searchable(),
                TextColumn::make('area.nombre')->label('Área')
                    ->searchable(),
                TextColumn::make('tipo.nombre')->label('Tipo')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('marca.nombre')->label('Marca')
                    ->searchable(),
                TextColumn::make('modelo')->label('Modelo')
                    ->searchable(),
                TextColumn::make('serie')->label('Serie')
                    ->searchable(),
                TextColumn::make('capacidad_valor')->label('Capacidad')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('capacidad_unidad')->label('Unidad')
                    ->searchable(),
                TextColumn::make('refrigerante.nombre')->label('Refrigerante')
                    ->searchable(),
                TextColumn::make('voltaje')->label('Voltaje')
                    ->searchable(),
                TextColumn::make('corriente')
                    ->searchable(),
                TextColumn::make('fecha_instalacion')
                    ->date()
                    ->sortable(),
                TextColumn::make('garantia_hasta')
                    ->date()
                    ->sortable(),
                TextColumn::make('estado')
                    ->badge()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('etiqueta')
                    ->label('Etiqueta')
                    ->icon('heroicon-o-qr-code')
                    ->url(fn ($record) => route('etiquetas.equipo', $record))
                    ->openUrlInNewTab(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('etiquetas')
                        ->label('Imprimir etiquetas')
                        ->icon('heroicon-o-printer')
                        ->action(fn (\Illuminate\Support\Collection $records) => redirect()->route('etiquetas.lote', ['ids' => $records->pluck('id')->implode(',')])),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
