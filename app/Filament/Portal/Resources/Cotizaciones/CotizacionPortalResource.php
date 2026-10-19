<?php

namespace App\Filament\Portal\Resources\Cotizaciones;

use App\Filament\Portal\Resources\Cotizaciones\Pages\ListCotizacionesPortal;
use App\Models\Cotizacion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CotizacionPortalResource extends Resource
{
    protected static ?string $model = Cotizacion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCurrencyDollar;

    protected static ?string $slug = 'cotizaciones';

    protected static ?string $modelLabel = 'Cotización';

    protected static ?string $pluralModelLabel = 'Mis cotizaciones';

    /** Candado 1: cliente del usuario y solo enviadas o aprobadas (igual que la política RLS). */
    public static function getEloquentQuery(): Builder
    {
        $clienteId = auth()->user()?->cliente_id;

        return parent::getEloquentQuery()
            ->whereIn('estado', ['enviada', 'aprobada'])
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
            ->defaultSort('fecha', 'desc')
            ->columns([
                TextColumn::make('numero')->label('Cotización')->searchable(),
                TextColumn::make('fecha')->date('d/m/Y')->sortable(),
                TextColumn::make('valida_hasta')->label('Válida hasta')->date('d/m/Y'),
                TextColumn::make('total')->money('HNL'),
                TextColumn::make('estado')->badge()->color(fn (string $state) => $state === 'aprobada' ? 'success' : 'info'),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListCotizacionesPortal::route('/')];
    }
}
