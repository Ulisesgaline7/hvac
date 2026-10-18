<?php

namespace App\Filament\Resources\Tecnicos\Tables;

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

class TecnicosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('Técnico')->searchable()->sortable(),
                TextColumn::make('user.email')->label('Correo')->toggleable(),
                TextColumn::make('telefono')->label('Teléfono'),
                TextColumn::make('zona'),
                TextColumn::make('estado_homologacion')
                    ->label('Homologación')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'aprobado' => 'success',
                        'suspendido' => 'danger',
                        default => 'warning',
                    }),
            ])
            ->filters([
                SelectFilter::make('estado_homologacion')
                    ->label('Homologación')
                    ->options(['pendiente' => 'Pendiente', 'aprobado' => 'Aprobado', 'suspendido' => 'Suspendido']),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('aprobar')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->estado_homologacion !== 'aprobado')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->update(['estado_homologacion' => 'aprobado'])),
                Action::make('suspender')
                    ->label('Suspender')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn ($record) => $record->estado_homologacion === 'aprobado')
                    ->requiresConfirmation()
                    ->modalDescription('El técnico dejará de ver sus OT y equipos de inmediato.')
                    ->action(fn ($record) => $record->update(['estado_homologacion' => 'suspendido'])),
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
