<?php

namespace App\Filament\Resources\Colaboradores\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ColaboradoresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('vinculoActivo'))
            ->defaultSort('nombre')
            ->columns([
                TextColumn::make('nombre')->searchable()->sortable(),
                TextColumn::make('vinculoActivo.tipo')->label('Vínculo')->badge()
                    ->formatStateUsing(fn ($state) => $state ? ucfirst($state) : 'Sin vínculo')
                    ->color(fn ($state) => match ($state) {
                        'empleado' => 'success',
                        'independiente' => 'info',
                        'externo' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('vinculoActivo.cargo')->label('Cargo'),
                TextColumn::make('vinculoActivo.fecha_inicio')->label('Desde')->date(),
                TextColumn::make('telefono')->toggleable(),
                TextColumn::make('rtn')->label('RTN')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('estado')->badge()
                    ->color(fn ($state) => $state === 'activo' ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('estado')->options(['activo' => 'Activo', 'inactivo' => 'Inactivo']),
                SelectFilter::make('tipo')->label('Vínculo')
                    ->options(['empleado' => 'Empleado', 'independiente' => 'Independiente', 'externo' => 'Externo'])
                    ->query(fn (Builder $query, array $data) => filled($data['value'] ?? null)
                        ? $query->whereHas('vinculoActivo', fn ($v) => $v->where('tipo', $data['value']))
                        : $query),
                TrashedFilter::make(),
            ])
            ->recordActions([EditAction::make()]);
    }
}
