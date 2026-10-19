<?php

namespace App\Filament\Portal\Resources\Facturas;

use App\Filament\Portal\Resources\Facturas\Pages\ListFacturasPortal;
use App\Models\Factura;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FacturaPortalResource extends Resource
{
    protected static ?string $model = Factura::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $slug = 'facturas';

    protected static ?string $modelLabel = 'Factura';

    protected static ?string $pluralModelLabel = 'Mis facturas';

    /** Candado 1: solo facturas del cliente del usuario. El candado 2 es RLS en Postgres. */
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
            ->defaultSort('fecha', 'desc')
            ->columns([
                TextColumn::make('numero')->label('Factura')->searchable(),
                TextColumn::make('fecha')->date('d/m/Y')->sortable(),
                TextColumn::make('vence')->date('d/m/Y'),
                TextColumn::make('total')->money('HNL'),
                TextColumn::make('pagado')->money('HNL'),
                TextColumn::make('saldo')->label('Saldo')->money('HNL')->state(fn (Factura $r) => $r->saldo),
                TextColumn::make('estado')->badge()->color(fn (string $state) => match ($state) {
                    'pagada' => 'success', 'parcial' => 'warning', 'anulada' => 'gray', default => 'info',
                }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListFacturasPortal::route('/')];
    }
}
