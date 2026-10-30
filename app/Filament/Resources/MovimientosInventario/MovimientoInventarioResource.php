<?php

namespace App\Filament\Resources\MovimientosInventario;

use App\Filament\Resources\MovimientosInventario\Pages\ListMovimientosInventario;
use App\Models\MovimientoInventario;
use App\Models\OrdenTrabajo;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MovimientoInventarioResource extends Resource
{
    protected static ?string $model = MovimientoInventario::class;

    protected static ?string $slug = 'movimientos-inventario';

    protected static ?string $modelLabel = 'movimiento';

    protected static ?string $pluralModelLabel = 'Movimientos de inventario';

    protected static ?string $navigationLabel = 'Movimientos';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventario';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function etiquetaOt(?OrdenTrabajo $ot): ?string
    {
        if (! $ot) {
            return null;
        }

        return (string) ($ot->numero ?? $ot->codigo ?? $ot->folio ?? ('OT #' . $ot->id));
    }

    public static function columnas(bool $conInsumo = true): array
    {
        $cols = [
            TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
        ];

        if ($conInsumo) {
            $cols[] = TextColumn::make('insumo.nombre')->label('Insumo')->searchable()->wrap();
        }

        return array_merge($cols, [
            TextColumn::make('tipo')->badge()->formatStateUsing(fn ($state) => ucfirst($state))
                ->color(fn ($state) => match ($state) {
                    'entrada' => 'success',
                    'salida' => 'danger',
                    default => 'warning',
                }),
            TextColumn::make('cantidad')->numeric(2)
                ->formatStateUsing(fn ($state) => ((float) $state > 0 ? '+' : '') . number_format((float) $state, 2))
                ->color(fn ($state) => (float) $state < 0 ? 'danger' : ((float) $state > 0 ? 'success' : 'gray')),
            TextColumn::make('stock_resultante')->label('Stock resultante')->numeric(2),
            TextColumn::make('costo_unitario')->label('Costo unit.')->numeric(2)->prefix('L ')->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('ordenTrabajo')->label('Orden')
                ->getStateUsing(fn (MovimientoInventario $r) => static::etiquetaOt($r->ordenTrabajo))
                ->placeholder('—'),
            TextColumn::make('user.name')->label('Usuario')->placeholder('—'),
            TextColumn::make('motivo')->limit(40)->tooltip(fn (MovimientoInventario $r) => $r->motivo)->placeholder('—'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['insumo', 'user', 'ordenTrabajo']))
            ->defaultSort('created_at', 'desc')
            ->columns(static::columnas())
            ->filters([
                SelectFilter::make('tipo')->options(['entrada' => 'Entrada', 'salida' => 'Salida', 'ajuste' => 'Ajuste']),
                SelectFilter::make('insumo_id')->label('Insumo')->relationship('insumo', 'nombre')->searchable()->preload(),
                Filter::make('fecha')
                    ->schema([
                        DatePicker::make('desde')->label('Desde'),
                        DatePicker::make('hasta')->label('Hasta'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['desde'] ?? null, fn ($query, $d) => $query->whereDate('created_at', '>=', $d))
                        ->when($data['hasta'] ?? null, fn ($query, $d) => $query->whereDate('created_at', '<=', $d))),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListMovimientosInventario::route('/')];
    }
}
