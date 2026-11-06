<?php

namespace App\Filament\Resources\Proveedores;

use App\Filament\Resources\Proveedores\Pages\CreateProveedor;
use App\Filament\Resources\Proveedores\Pages\EditProveedor;
use App\Filament\Resources\Proveedores\Pages\ListProveedores;
use App\Models\Proveedor;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProveedorResource extends Resource
{
    protected static ?string $model = Proveedor::class;

    protected static ?string $slug = 'proveedores';

    protected static ?string $modelLabel = 'proveedor';

    protected static ?string $pluralModelLabel = 'Proveedores';

    protected static ?string $navigationLabel = 'Proveedores';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventario';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Datos del proveedor')->columns(2)->schema([
                TextInput::make('nombre')->required()->maxLength(150),
                TextInput::make('rtn')->label('RTN')->maxLength(20),
                TextInput::make('contacto')->maxLength(100),
                TextInput::make('telefono')->label('Teléfono')->tel()->maxLength(30),
                TextInput::make('email')->label('Correo')->email()->maxLength(120),
                TextInput::make('direccion')->label('Dirección')->maxLength(255),
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
                TextColumn::make('nombre')->searchable()->sortable()->wrap(),
                TextColumn::make('rtn')->label('RTN')->searchable()->toggleable(),
                TextColumn::make('contacto')->searchable()->toggleable(),
                TextColumn::make('telefono')->label('Teléfono')->toggleable(),
                TextColumn::make('insumos_count')->counts('insumos')->label('Insumos')->sortable(),
                IconColumn::make('activo')->boolean(),
            ])
            ->filters([TernaryFilter::make('activo')->label('Activo')])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProveedores::route('/'),
            'create' => CreateProveedor::route('/create'),
            'edit' => EditProveedor::route('/{record}/edit'),
        ];
    }
}
