<?php

namespace App\Filament\Resources\Refrigerantes;

use App\Filament\Resources\Refrigerantes\Pages\CreateRefrigerante;
use App\Filament\Resources\Refrigerantes\Pages\EditRefrigerante;
use App\Filament\Resources\Refrigerantes\Pages\ListRefrigerantes;
use App\Filament\Resources\Refrigerantes\Schemas\RefrigeranteForm;
use App\Filament\Resources\Refrigerantes\Tables\RefrigerantesTable;
use App\Models\Refrigerante;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RefrigeranteResource extends Resource
{
    protected static ?string $model = Refrigerante::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return RefrigeranteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RefrigerantesTable::configure($table);
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
            'index' => ListRefrigerantes::route('/'),
            'create' => CreateRefrigerante::route('/create'),
            'edit' => EditRefrigerante::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    protected static ?string $slug = 'refrigerantes';

    public static function getModelLabel(): string
    {
        return 'Refrigerante';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Refrigerantes';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Catálogos';
    }
}
