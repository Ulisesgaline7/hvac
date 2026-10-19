<?php

namespace App\Filament\Portal\Resources\OrdenesTrabajo;

use App\Filament\Portal\Resources\OrdenesTrabajo\Pages\ListOrdenesTrabajoPortal;
use App\Models\Informe;
use App\Models\OrdenTrabajo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrdenTrabajoPortalResource extends Resource
{
    protected static ?string $model = OrdenTrabajo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?string $slug = 'ordenes-trabajo';

    protected static ?string $modelLabel = 'Orden de trabajo';

    protected static ?string $pluralModelLabel = 'Mis órdenes de trabajo';

    /** Candado 1: solo OT del cliente del usuario. */
    public static function getEloquentQuery(): Builder
    {
        $clienteId = auth()->user()?->cliente_id;

        return parent::getEloquentQuery()
            ->when($clienteId, fn (Builder $q) => $q->where('cliente_id', $clienteId), fn (Builder $q) => $q->whereRaw('1 = 0'));
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('fecha_programada', 'desc')
            ->columns([
                TextColumn::make('numero')->label('OT')->searchable(),
                TextColumn::make('tipo')->badge(),
                TextColumn::make('estado')->badge(),
                TextColumn::make('sucursal.nombre')->label('Sucursal'),
                TextColumn::make('fecha_programada')->label('Programada')->date('d/m/Y')->sortable(),
                IconColumn::make('informe_publicado')->label('Informe')->boolean()
                    ->state(fn (OrdenTrabajo $r) => Informe::where('orden_trabajo_id', $r->id)->where('estado', 'publicado')->exists()),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListOrdenesTrabajoPortal::route('/')];
    }
}
