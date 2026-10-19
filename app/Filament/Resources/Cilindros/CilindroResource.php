<?php

namespace App\Filament\Resources\Cilindros;

use App\Filament\Resources\Cilindros\Pages\CreateCilindro;
use App\Filament\Resources\Cilindros\Pages\EditCilindro;
use App\Filament\Resources\Cilindros\Pages\ListCilindros;
use App\Filament\Resources\Cilindros\Schemas\CilindroForm;
use App\Filament\Resources\Cilindros\Tables\CilindrosTable;
use App\Models\CilindroRefrigerante;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CilindroResource extends Resource
{
    protected static ?string $model = CilindroRefrigerante::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBeaker;

    protected static ?string $slug = 'cilindros';

    public static function form(Schema $schema): Schema
    {
        return CilindroForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CilindrosTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCilindros::route('/'),
            'create' => CreateCilindro::route('/create'),
            'edit' => EditCilindro::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    private static function esGestor(): bool
    {
        return auth()->user()?->hasAnyRole(['administrador', 'coordinador']) ?? false;
    }

    public static function canCreate(): bool
    {
        return self::esGestor();
    }

    public static function canEdit($record): bool
    {
        return self::esGestor();
    }

    public static function getModelLabel(): string
    {
        return 'Cilindro';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Cilindros de refrigerante';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Catálogos';
    }
}
