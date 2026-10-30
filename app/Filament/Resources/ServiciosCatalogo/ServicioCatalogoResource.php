<?php

namespace App\Filament\Resources\ServiciosCatalogo;

use App\Filament\Resources\ServiciosCatalogo\Pages\ManageServiciosCatalogo;
use App\Models\ServicioCatalogo;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class ServicioCatalogoResource extends Resource
{
    protected static ?string $model = ServicioCatalogo::class;

    protected static ?string $slug = 'servicios-catalogo';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static string|UnitEnum|null $navigationGroup = 'Tarifario';

    protected static ?string $navigationLabel = 'Servicios y precios';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'servicio';

    protected static ?string $pluralModelLabel = 'servicios y precios';

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function categorias(): array
    {
        return collect(config('tarifario.categorias', []))->keys()
            ->mapWithKeys(fn ($k) => [$k => Str::headline($k)])->all();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('categoria')->label('Categoría')->options(fn () => static::categorias())->required(),
            TextInput::make('codigo')->label('Código')->required()->maxLength(255)->unique(ignoreRecord: true),
            TextInput::make('nombre')->required()->maxLength(255)->columnSpanFull(),
            TextInput::make('detalle')->maxLength(255)->columnSpanFull(),
            TextInput::make('precio')->label('Precio')->numeric()->required()->minValue(0)->prefix('L')
                ->helperText('Sin ISV.'),
            TextInput::make('precio_hasta')->label('Precio hasta (opcional)')->numeric()->minValue(0)->prefix('L')
                ->helperText('Solo si el servicio es un rango: «desde — hasta».'),
            Toggle::make('cuenta_equipo')->label('Cuenta como equipo')
                ->helperText('Cuenta para el servicio mínimo y los descuentos por volumen.'),
            Toggle::make('activo')->default(true)
                ->helperText('Desactívalo en lugar de borrarlo: las cotizaciones anteriores conservan su precio.'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo')->label('Código')->searchable()->sortable(),
                TextColumn::make('categoria')->label('Categoría')->badge()
                    ->formatStateUsing(fn ($state) => Str::headline((string) $state))->sortable(),
                TextColumn::make('nombre')->searchable()->wrap(),
                TextColumn::make('detalle')->wrap()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('precio')->label('Precio')->numeric(2)->prefix('L ')->sortable(),
                TextColumn::make('precio_hasta')->label('Hasta')->numeric(2)->prefix('L ')->placeholder('—'),
                IconColumn::make('cuenta_equipo')->label('Equipo')->boolean()->toggleable(),
                ToggleColumn::make('activo')->label('Activo'),
            ])
            ->defaultSort('codigo')
            ->filters([
                SelectFilter::make('categoria')->label('Categoría')->options(fn () => static::categorias()),
                TernaryFilter::make('activo')->label('Activo'),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageServiciosCatalogo::route('/')];
    }
}
