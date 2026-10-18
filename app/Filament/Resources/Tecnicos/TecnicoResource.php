<?php

namespace App\Filament\Resources\Tecnicos;

use App\Filament\Resources\Tecnicos\Pages\CreateTecnico;
use App\Filament\Resources\Tecnicos\Pages\EditTecnico;
use App\Filament\Resources\Tecnicos\Pages\ListTecnicos;
use App\Filament\Resources\Tecnicos\RelationManagers\DocumentosRelationManager;
use App\Filament\Resources\Tecnicos\Schemas\TecnicoForm;
use App\Filament\Resources\Tecnicos\Tables\TecnicosTable;
use App\Models\Tecnico;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TecnicoResource extends Resource
{
    protected static ?string $model = Tecnico::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?string $slug = 'tecnicos';

    public static function form(Schema $schema): Schema
    {
        return TecnicoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TecnicosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [DocumentosRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTecnicos::route('/'),
            'create' => CreateTecnico::route('/create'),
            'edit' => EditTecnico::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getModelLabel(): string
    {
        return 'Técnico';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Técnicos';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Operación';
    }
}
