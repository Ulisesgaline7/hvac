<?php

namespace App\Filament\Resources\BibliotecaDocumentos\RelationManagers;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VersionesRelationManager extends RelationManager
{
    protected static string $relationship = 'versiones';

    protected static ?string $title = 'Historial de versiones';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('version')->label('Versión'),
                TextColumn::make('created_at')->label('Reemplazada el')->dateTime('d/m/Y H:i'),
                TextColumn::make('creado_por')->label('Por')
                    ->getStateUsing(fn ($record) => User::find($record->creado_por)?->name ?? '—'),
            ])
            ->recordActions([
                Action::make('restaurar')->label('Restaurar')->icon('heroicon-o-arrow-uturn-left')
                    ->requiresConfirmation()
                    ->modalDescription('El contenido actual se guarda en el historial antes de restaurar esta versión.')
                    ->action(function ($record) {
                        $this->getOwnerRecord()->update([
                            'contenido' => $record->contenido,
                            'version' => $record->version,
                        ]);
                        $this->js('window.location.reload()');
                    }),
            ]);
    }
}
