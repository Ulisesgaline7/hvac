<?php

namespace App\Filament\Portal\Resources\Contratos;

use App\Filament\Portal\Resources\Contratos\Pages\ListContratosPortal;
use App\Models\Contrato;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContratoPortalResource extends Resource
{
    protected static ?string $model = Contrato::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $slug = 'contratos';

    protected static ?string $modelLabel = 'Contrato';

    protected static ?string $pluralModelLabel = 'Mis contratos';

    public static function getEloquentQuery(): Builder
    {
        $clienteId = auth()->user()?->cliente_id;

        return parent::getEloquentQuery()
            ->whereIn('estado', ['activo', 'vencido'])
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
            ->defaultSort('vigencia_inicio', 'desc')
            ->columns([
                TextColumn::make('id')->label('N.º')->formatStateUsing(fn ($state) => str_pad((string) $state, 5, '0', STR_PAD_LEFT)),
                TextColumn::make('tipo')->badge(),
                TextColumn::make('vigencia_inicio')->label('Desde')->date('d/m/Y'),
                TextColumn::make('vigencia_fin')->label('Hasta')->date('d/m/Y'),
                TextColumn::make('periodicidad'),
                TextColumn::make('cuota_monto')->label('Cuota')->money('HNL'),
                TextColumn::make('estado')->badge(),
            ]);
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Comercial';
    }

    public static function getPages(): array
    {
        return ['index' => ListContratosPortal::route('/')];
    }
}
