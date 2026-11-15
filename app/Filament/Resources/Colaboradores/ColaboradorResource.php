<?php

namespace App\Filament\Resources\Colaboradores;

use App\Filament\Resources\Colaboradores\Pages\CreateColaborador;
use App\Filament\Resources\Colaboradores\Pages\EditColaborador;
use App\Filament\Resources\Colaboradores\Pages\ListColaboradores;
use App\Filament\Resources\Colaboradores\RelationManagers\DocumentosRelationManager;
use App\Filament\Resources\Colaboradores\RelationManagers\VinculosRelationManager;
use App\Filament\Resources\Colaboradores\Schemas\ColaboradorForm;
use App\Filament\Resources\Colaboradores\Tables\ColaboradoresTable;
use App\Models\Colaborador;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ColaboradorResource extends Resource
{
    protected static ?string $model = Colaborador::class;

    protected static ?string $slug = 'colaboradores';

    protected static ?string $modelLabel = 'colaborador';

    protected static ?string $pluralModelLabel = 'colaboradores';

    protected static ?string $recordTitleAttribute = 'nombre';

    protected static string|\UnitEnum|null $navigationGroup = 'Recursos humanos';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    /** Solo administrador y rrhh: la ficha incluye identidad, cuenta bancaria y sueldos. */
    private static function permitido(): bool
    {
        return auth()->user()?->hasAnyRole(['administrador', 'rrhh']) ?? false;
    }

    public static function canViewAny(): bool { return self::permitido(); }
    public static function canCreate(): bool { return self::permitido(); }
    public static function canView(Model $record): bool { return self::permitido(); }
    public static function canEdit(Model $record): bool { return self::permitido(); }
    public static function canDelete(Model $record): bool { return self::permitido(); }
    public static function canDeleteAny(): bool { return self::permitido(); }
    public static function canRestore(Model $record): bool { return self::permitido(); }

    public static function form(Schema $schema): Schema
    {
        return ColaboradorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ColaboradoresTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [VinculosRelationManager::class, DocumentosRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListColaboradores::route('/'),
            'create' => CreateColaborador::route('/create'),
            'edit' => EditColaborador::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
