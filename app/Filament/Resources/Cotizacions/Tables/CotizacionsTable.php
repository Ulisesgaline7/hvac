<?php

namespace App\Filament\Resources\Cotizacions\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CotizacionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('numero')->label('Número')->searchable()->sortable(),
                TextColumn::make('cliente.razon_social')->label('Cliente')->searchable(),
                TextColumn::make('sucursal.nombre')->label('Sucursal'),
                TextColumn::make('fecha')->label('Fecha')->date('d/m/Y')->sortable(),
                TextColumn::make('valida_hasta')->label('Válida hasta')->date('d/m/Y'),
                TextColumn::make('estado')->label('Estado')->badge()
                    ->color(fn (string $state) => match ($state) {
                        'aprobada' => 'success',
                        'rechazada' => 'danger',
                        'enviada' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('total')->label('Total')->sortable()
                    ->formatStateUsing(fn ($state) => 'L ' . number_format((float) $state, 2)),
            ])
            ->filters([
                SelectFilter::make('estado')->options([
                    'borrador' => 'Borrador', 'enviada' => 'Enviada', 'aprobada' => 'Aprobada', 'rechazada' => 'Rechazada',
                ]),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('pdf')->label('PDF')->icon('heroicon-o-document-arrow-down')
                    ->url(fn ($record) => route('cotizaciones.pdf', $record))->openUrlInNewTab(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make(), ForceDeleteBulkAction::make(), RestoreBulkAction::make()]),
            ]);
    }
}
