<?php

namespace App\Filament\Resources\PlantillaAjusteClientes;

use App\Filament\Resources\PlantillaAjusteClientes\Pages\CreatePlantillaAjusteCliente;
use App\Filament\Resources\PlantillaAjusteClientes\Pages\EditPlantillaAjusteCliente;
use App\Filament\Resources\PlantillaAjusteClientes\Pages\ListPlantillaAjusteClientes;
use App\Models\PlantillaAjusteCliente;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlantillaAjusteClienteResource extends Resource
{
    public static function canViewAny(): bool
    {
        $u = auth()->user();

        return ! ($u?->hasRole('comercial') && ! $u->hasAnyRole(['administrador', 'coordinador']));
    }

    protected static string|\UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 5;

    protected static ?string $model = PlantillaAjusteCliente::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static ?string $modelLabel = 'ajuste por cliente';
    protected static ?string $pluralModelLabel = 'Ajustes por cliente';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('cliente_id')->label('Cliente')->relationship('cliente', 'razon_social')
                ->searchable()->preload()->required(),
            Select::make('ambito')->label('Aplica a')
                ->options(['registro' => 'Registro de equipos', 'ficha' => 'Fichas técnicas'])
                ->default('registro')->live()->required(),
            Select::make('tipo_equipo_id')->label('Tipo de equipo (vacío = todos)')
                ->relationship('tipoEquipo', 'nombre')->searchable()->preload()
                ->visible(fn (Get $get) => $get('ambito') === 'registro'),
            Select::make('plantilla_id')->label('Plantilla (vacío = todas)')
                ->relationship('plantilla', 'nombre')->searchable()->preload()
                ->visible(fn (Get $get) => $get('ambito') === 'ficha'),
            Textarea::make('ajustes')->label('Ajustes (JSON)')->rows(14)->columnSpanFull()->required()
                ->helperText('Claves: ocultar, obligatorio, rangos, agregar.')
                ->formatStateUsing(fn ($state) => is_array($state)
                    ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                    : ($state ?: "{\n  \"obligatorio\": {},\n  \"ocultar\": [],\n  \"agregar\": []\n}"))
                ->rule(fn () => function (string $attribute, $value, \Closure $fail) {
                    if (! is_array(json_decode((string) $value, true))) {
                        $fail('JSON no válido.');
                    }
                })
                ->dehydrateStateUsing(fn ($state) => json_decode((string) $state, true)),
            Toggle::make('activo')->default(true),
            Textarea::make('notas')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('cliente.razon_social')->label('Cliente')->searchable(),
            TextColumn::make('ambito')->label('Aplica a')->badge(),
            TextColumn::make('tipoEquipo.nombre')->label('Tipo')->placeholder('Todos'),
            TextColumn::make('plantilla.nombre')->label('Plantilla')->placeholder('Todas'),
            IconColumn::make('activo')->boolean(),
        ])->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlantillaAjusteClientes::route('/'),
            'create' => CreatePlantillaAjusteCliente::route('/create'),
            'edit' => EditPlantillaAjusteCliente::route('/{record}/edit'),
        ];
    }
}
