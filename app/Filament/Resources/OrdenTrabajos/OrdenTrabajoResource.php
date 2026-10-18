<?php

namespace App\Filament\Resources\OrdenTrabajos;

use App\Filament\Resources\OrdenTrabajos\Pages\CreateOrdenTrabajo;
use App\Filament\Resources\OrdenTrabajos\Pages\EditOrdenTrabajo;
use App\Filament\Resources\OrdenTrabajos\Pages\ListOrdenTrabajos;
use App\Filament\Resources\OrdenTrabajos\RelationManagers\HistorialRelationManager;
use App\Filament\Resources\OrdenTrabajos\Schemas\OrdenTrabajoForm;
use App\Filament\Resources\OrdenTrabajos\Tables\OrdenTrabajosTable;
use App\Models\OrdenTrabajo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrdenTrabajoResource extends Resource
{
    protected static ?string $model = OrdenTrabajo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $slug = 'ordenes-trabajo';

    protected static ?string $recordTitleAttribute = 'numero';

    public static function form(Schema $schema): Schema
    {
        return OrdenTrabajoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrdenTrabajosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [HistorialRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrdenTrabajos::route('/'),
            'create' => CreateOrdenTrabajo::route('/create'),
            'edit' => EditOrdenTrabajo::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getModelLabel(): string
    {
        return 'Orden de trabajo';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Órdenes de trabajo';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Operación';
    }
}
