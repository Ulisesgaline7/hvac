<?php

namespace App\Filament\Resources\Equipos;

use App\Filament\Resources\Equipos\Pages\CreateEquipo;
use App\Filament\Resources\Equipos\Pages\EditEquipo;
use App\Filament\Resources\Equipos\Pages\ListEquipos;
use App\Filament\Resources\Equipos\Schemas\EquipoForm;
use App\Filament\Resources\Equipos\Tables\EquiposTable;
use App\Models\Equipo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EquipoResource extends Resource
{
    protected static ?string $model = Equipo::class;
    protected static ?string $modelLabel = 'equipo';

    protected static ?string $pluralModelLabel = 'equipos';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return EquipoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EquiposTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        $texto = fn (string $n, string $l) => \Filament\Infolists\Components\TextEntry::make($n)->label($l)->placeholder('—');

        return $schema->components([
            \Filament\Schemas\Components\Section::make('Equipo')->columns(3)->columnSpanFull()->schema([
                \Filament\Infolists\Components\ImageEntry::make('foto_ruta')->label('Foto')->disk('public')->height(160),
                $texto('codigo', 'Código')->weight('bold')->copyable(),
                $texto('tipo.nombre', 'Tipo'),
                \Filament\Infolists\Components\TextEntry::make('estado')->label('Estado')->badge(),
                $texto('marca.nombre', 'Marca'),
                $texto('modelo', 'Modelo'),
                $texto('serie', 'Serie'),
                $texto('capacidad_texto', 'Capacidad'),
                $texto('refrigerante.nombre', 'Refrigerante'),
                $texto('voltaje', 'Voltaje'),
            ]),
            \Filament\Schemas\Components\Section::make('Ubicación y garantía')->columns(3)->columnSpanFull()->schema([
                $texto('area.nombre', 'Área'),
                $texto('area.sucursal.nombre', 'Sucursal'),
                $texto('area.sucursal.cliente.razon_social', 'Cliente'),
                $texto('fecha_instalacion', 'Instalado')->date('d/m/Y'),
                $texto('garantia_hasta', 'Garantía hasta')->date('d/m/Y'),
                $texto('notas', 'Notas')->columnSpanFull(),
            ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\OrdenesEquipoRelationManager::class,
            RelationManagers\GarantiasEquipoRelationManager::class,
            RelationManagers\DocumentosEquipoRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEquipos::route('/'),
            'create' => CreateEquipo::route('/create'),
            'view' => Pages\ViewEquipo::route('/{record}'),
            'edit' => EditEquipo::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    protected static ?string $slug = 'equipos';

    public static function getModelLabel(): string
    {
        return 'Equipo';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Equipos';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Equipos';
    }
}
