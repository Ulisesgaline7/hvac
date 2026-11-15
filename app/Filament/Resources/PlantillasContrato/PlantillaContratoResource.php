<?php

namespace App\Filament\Resources\PlantillasContrato;

use App\Filament\Resources\PlantillasContrato\Pages\ManagePlantillasContrato;
use App\Models\PlantillaContrato;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class PlantillaContratoResource extends Resource
{
    public static function canViewAny(): bool
    {
        $u = auth()->user();

        return ! ($u?->hasRole('comercial') && ! $u->hasAnyRole(['administrador', 'coordinador']));
    }

    protected static string|\UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 3;

    protected static ?string $model = PlantillaContrato::class;

    protected static ?string $slug = 'plantillas-contrato';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;


    protected static ?string $navigationLabel = 'Plantillas de contrato';
    protected static ?string $navigationParentItem = 'Documentos';


    protected static ?string $modelLabel = 'plantilla de contrato';

    protected static ?string $pluralModelLabel = 'plantillas de contrato';

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function canAccess(): bool
    {
        return (bool) Auth::user()?->hasAnyRole(['administrador', 'coordinador']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('clave')->disabled()->dehydrated(false),
            TextInput::make('nombre')->required(),
            Toggle::make('activa')->helperText('Si se desactiva, los contratos nuevos nacen sin este texto.'),
            Textarea::make('servicios_incluidos')->rows(5)->columnSpanFull(),
            Textarea::make('exclusiones')->rows(5)->columnSpanFull(),
            Textarea::make('garantias')->rows(5)->columnSpanFull(),
            KeyValue::make('sla')->label('Nivel de servicio')->keyLabel('Concepto')->valueLabel('Valor')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('clave')->badge(),
                TextColumn::make('nombre')->wrap(),
                TextColumn::make('visitas_anuales')->label('Visitas'),
                TextColumn::make('periodicidad')->label('Cobro'),
                IconColumn::make('activa')->boolean(),
            ])
            ->defaultSort('id')
            ->paginated(false)
            ->recordActions([EditAction::make()->modalWidth('4xl')]);
    }

    public static function getPages(): array
    {
        return ['index' => ManagePlantillasContrato::route('/')];
    }
}
