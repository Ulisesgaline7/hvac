<?php

namespace App\Filament\Resources\Insumos;

use App\Filament\Resources\Insumos\Pages\CreateInsumo;
use App\Filament\Resources\Insumos\Pages\EditInsumo;
use App\Filament\Resources\Insumos\Pages\ListInsumos;
use App\Filament\Resources\Insumos\RelationManagers\MovimientosRelationManager;
use App\Filament\Resources\MovimientosInventario\MovimientoInventarioResource;
use App\Models\Insumo;
use App\Models\OrdenTrabajo;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InsumoResource extends Resource
{
    protected static ?string $model = Insumo::class;

    protected static ?string $slug = 'insumos';

    protected static ?string $modelLabel = 'insumo';

    protected static ?string $pluralModelLabel = 'Insumos';

    protected static ?string $navigationLabel = 'Insumos';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventario';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function bajoMinimo(Builder $query): Builder
    {
        return $query->where('stock_minimo', '>', 0)->whereColumn('stock', '<=', 'stock_minimo');
    }

    public static function getNavigationBadge(): ?string
    {
        $n = static::bajoMinimo(Insumo::query()->where('activo', true))->count();

        return $n > 0 ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Insumos en o bajo el stock mínimo';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Datos del insumo')
                ->columns(2)
                ->schema([
                    TextInput::make('codigo')->label('Código')->required()->maxLength(40)->unique(ignoreRecord: true),
                    TextInput::make('nombre')->required()->maxLength(160),
                    TextInput::make('categoria')->label('Categoría')->maxLength(60)
                        ->datalist(fn () => Insumo::query()->whereNotNull('categoria')->distinct()->orderBy('categoria')->pluck('categoria')->all()),
                    TextInput::make('unidad')->required()->default('unidad')->maxLength(20)
                        ->datalist(['unidad', 'metro', 'pie', 'kg', 'lb', 'litro', 'galón', 'rollo', 'caja', 'par']),
                    TextInput::make('ubicacion')->label('Ubicación')->maxLength(80)->columnSpanFull(),
                ]),
            Section::make('Costos y existencias')
                ->columns(2)
                ->schema([
                    TextInput::make('costo')->label('Costo unitario')->numeric()->prefix('L')->default(0)->minValue(0)->required(),
                    TextInput::make('precio_venta')->label('Precio de venta')->numeric()->prefix('L')->minValue(0),
                    TextInput::make('stock_minimo')->label('Stock mínimo')->numeric()->default(0)->minValue(0)->required()
                        ->helperText('Se alerta cuando el stock llega a este nivel o menos.'),
                    TextInput::make('stock_inicial')->label('Stock inicial')->numeric()->default(0)->minValue(0)
                        ->visibleOn('create')
                        ->helperText('Se registra como un movimiento de entrada.'),
                    TextEntry::make('stock')->label('Stock actual')->visibleOn('edit')
                        ->formatStateUsing(fn ($state, $record) => number_format((float) $state, 2) . ' ' . $record?->unidad)
                        ->hintIcon('heroicon-o-information-circle', 'El stock solo cambia con movimientos: Entrada, Salida o Ajuste (en la lista).'),
                ]),
            Section::make('Otros')
                ->schema([
                    Textarea::make('notas')->rows(3)->columnSpanFull(),
                    Toggle::make('activo')->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('nombre')
            ->columns([
                TextColumn::make('codigo')->label('Código')->searchable()->sortable(),
                TextColumn::make('nombre')->searchable()->sortable()->wrap(),
                TextColumn::make('categoria')->label('Categoría')->searchable()->sortable()->toggleable(),
                TextColumn::make('stock')->label('Stock')->numeric(2)->sortable()->badge()
                    ->suffix(fn (Insumo $r) => ' ' . $r->unidad)
                    ->color(fn (Insumo $r) => (float) $r->stock_minimo > 0 && (float) $r->stock <= (float) $r->stock_minimo ? 'danger' : 'success'),
                TextColumn::make('stock_minimo')->label('Mínimo')->numeric(2)->toggleable(),
                TextColumn::make('costo')->label('Costo')->numeric(2)->prefix('L ')->sortable()->toggleable(),
                TextColumn::make('precio_venta')->label('Precio')->numeric(2)->prefix('L ')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ubicacion')->label('Ubicación')->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('activo')->boolean()->toggleable(),
            ])
            ->filters([
                Filter::make('bajo_minimo')->label('Bajo stock mínimo')->toggle()
                    ->query(fn (Builder $query) => static::bajoMinimo($query)),
                SelectFilter::make('categoria')->label('Categoría')
                    ->options(fn () => Insumo::query()->whereNotNull('categoria')->distinct()->orderBy('categoria')->pluck('categoria', 'categoria')->all()),
                TernaryFilter::make('activo')->label('Activo'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    static::accionMovimiento('entrada'),
                    static::accionMovimiento('salida'),
                    static::accionMovimiento('ajuste'),
                ])->label('Movimiento')->icon('heroicon-o-arrows-up-down')->button()->color('gray'),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    protected static function accionMovimiento(string $tipo): Action
    {
        [$label, $icono, $color] = match ($tipo) {
            'entrada' => ['Entrada', 'heroicon-o-arrow-down-tray', 'success'],
            'salida' => ['Salida', 'heroicon-o-arrow-up-tray', 'danger'],
            default => ['Ajuste (conteo)', 'heroicon-o-adjustments-horizontal', 'warning'],
        };

        $accion = Action::make($tipo)
            ->label($label)->icon($icono)->color($color)
            ->modalHeading(fn (Insumo $record) => "{$label}: {$record->nombre}")
            ->modalDescription(fn (Insumo $record) => 'Stock actual: ' . number_format((float) $record->stock, 2) . ' ' . $record->unidad)
            ->modalSubmitActionLabel('Registrar')
            ->schema([
                TextInput::make('cantidad')
                    ->label($tipo === 'ajuste' ? 'Cantidad contada (stock real)' : 'Cantidad')
                    ->numeric()->required()->minValue($tipo === 'ajuste' ? 0 : 0.01),
                TextInput::make('costo')->label('Costo unitario')->numeric()->prefix('L')->minValue(0)
                    ->visible($tipo === 'entrada')
                    ->helperText('Opcional. Si lo llenas, actualiza el costo del insumo.'),
                Select::make('orden_trabajo_id')->label('Orden de trabajo')
                    ->options(fn () => static::opcionesOt())->searchable()
                    ->visible($tipo === 'salida')
                    ->helperText('Opcional. Asocia el consumo a una orden.'),
                TextInput::make('motivo')->maxLength(255)->required($tipo === 'ajuste'),
            ])
            ->action(function (Insumo $record, array $data) use ($tipo) {
                try {
                    $costo = isset($data['costo']) && $data['costo'] !== '' ? (float) $data['costo'] : null;
                    $record->registrarMovimiento(
                        $tipo,
                        (float) $data['cantidad'],
                        isset($data['orden_trabajo_id']) ? (int) $data['orden_trabajo_id'] : null,
                        $costo,
                        $data['motivo'] ?? null,
                    );
                    Notification::make()->title('Movimiento registrado')->success()->send();
                } catch (DomainException $e) {
                    Notification::make()->title('No se pudo registrar')->body($e->getMessage())->danger()->send();
                }
            });

        if ($tipo === 'ajuste') {
            $accion->fillForm(fn (Insumo $record) => ['cantidad' => $record->stock]);
        }

        return $accion;
    }

    protected static function opcionesOt(): array
    {
        return OrdenTrabajo::query()->latest('id')->limit(300)->get()
            ->mapWithKeys(fn (OrdenTrabajo $o) => [$o->id => MovimientoInventarioResource::etiquetaOt($o)])
            ->all();
    }

    public static function getRelations(): array
    {
        return [MovimientosRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInsumos::route('/'),
            'create' => CreateInsumo::route('/create'),
            'edit' => EditInsumo::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
