<?php

namespace App\Filament\Resources\TipoEquipos;

use App\Filament\Resources\TipoEquipos\Pages\CreateTipoEquipo;
use App\Filament\Resources\TipoEquipos\Pages\EditTipoEquipo;
use App\Filament\Resources\TipoEquipos\Pages\ListTipoEquipos;
use App\Filament\Resources\TipoEquipos\Schemas\TipoEquipoForm;
use App\Filament\Resources\TipoEquipos\Tables\TipoEquiposTable;
use App\Models\TipoEquipo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TipoEquipoResource extends Resource
{
    protected static ?string $model = TipoEquipo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return TipoEquipoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TipoEquiposTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTipoEquipos::route('/'),
            'create' => CreateTipoEquipo::route('/create'),
            'edit' => EditTipoEquipo::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    protected static ?string $slug = 'tipos-equipo';

    public static function getModelLabel(): string
    {
        return 'Tipo de equipo';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Tipos de equipo';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Catálogos';
    }
}
