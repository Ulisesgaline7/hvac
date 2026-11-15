<?php

namespace App\Filament\Portal\Resources\Equipos;

use App\Filament\Portal\Resources\Equipos\Pages\ListEquiposPortal;
use App\Models\Equipo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EquipoPortalResource extends Resource
{
    protected static ?string $model = Equipo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static ?string $slug = 'equipos';

    protected static ?string $modelLabel = 'Equipo';

    protected static ?string $pluralModelLabel = 'Mis equipos';

    public static function getEloquentQuery(): Builder
    {
        $clienteId = auth()->user()?->cliente_id;

        return parent::getEloquentQuery()
            ->when(
                $clienteId,
                fn (Builder $q) => $q->whereIn('area_id', fn ($s) => $s->select('areas.id')->from('areas')
                    ->join('sucursales', 'sucursales.id', '=', 'areas.sucursal_id')
                    ->where('sucursales.cliente_id', $clienteId)),
                fn (Builder $q) => $q->whereRaw('1 = 0')
            );
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
            ->defaultSort('codigo')
            ->columns([
                TextColumn::make('codigo')->label('Código')->searchable(),
                TextColumn::make('tipo.nombre')->label('Tipo'),
                TextColumn::make('modelo')->searchable(),
                TextColumn::make('serie'),
                TextColumn::make('fecha_instalacion')->label('Instalado')->date('d/m/Y'),
                TextColumn::make('estado')->badge(),
            ]);
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Servicios';
    }

    public static function getPages(): array
    {
        return ['index' => ListEquiposPortal::route('/')];
    }
}
