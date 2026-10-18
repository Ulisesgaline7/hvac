<?php

namespace App\Filament\Resources\PlantillaFormularios;

use App\Filament\Resources\PlantillaFormularios\Pages\CreatePlantillaFormulario;
use App\Filament\Resources\PlantillaFormularios\Pages\EditPlantillaFormulario;
use App\Filament\Resources\PlantillaFormularios\Pages\ListPlantillaFormularios;
use App\Models\PlantillaFormulario;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlantillaFormularioResource extends Resource
{
    protected static ?string $model = PlantillaFormulario::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $slug = 'plantillas-formulario';

    public const TIPOS = [
        'texto' => 'Texto', 'numero' => 'Número con unidad', 'seleccion' => 'Selección', 'casilla' => 'Casilla',
        'checklist' => 'Lista de verificación', 'fecha' => 'Fecha', 'foto' => 'Foto', 'firma' => 'Firma', 'nota' => 'Nota larga',
    ];

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nombre')->required()->unique(ignoreRecord: true),
            Select::make('familia')->required()->options([
                'aire_acondicionado' => 'Aire acondicionado',
                'cuartos_frios' => 'Cuartos fríos',
                'refrigeracion_comercial' => 'Refrigeración comercial',
                'instalacion' => 'Instalación y puesta en marcha',
            ]),
            Textarea::make('descripcion')->columnSpanFull(),
            Repeater::make('campos')
                ->label('Campos del formulario')
                ->helperText('Cada guardado con cambios publica una versión nueva. Las fichas ya llenadas conservan la suya.')
                ->columnSpanFull()
                ->reorderable()
                ->collapsible()
                ->itemLabel(fn (array $state) => $state['etiqueta'] ?? 'Campo nuevo')
                ->columns(3)
                ->schema([
                    TextInput::make('etiqueta')->required(),
                    TextInput::make('clave')->required()->regex('/^[a-z0-9_]+$/')
                        ->helperText('Minúsculas, números y guion bajo. No cambiarla después.'),
                    Select::make('tipo')->options(self::TIPOS)->required()->live(),
                    Toggle::make('obligatorio'),
                    TextInput::make('unidad')->visible(fn (Get $get) => $get('tipo') === 'numero'),
                    TextInput::make('min')->numeric()->label('Mínimo esperado')->visible(fn (Get $get) => $get('tipo') === 'numero'),
                    TextInput::make('max')->numeric()->label('Máximo esperado')->visible(fn (Get $get) => $get('tipo') === 'numero'),
                    TagsInput::make('opciones')->visible(fn (Get $get) => in_array($get('tipo'), ['seleccion', 'checklist'], true))->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('nombre')->searchable(),
            TextColumn::make('familia')->badge(),
            TextColumn::make('ultimaVersion.version')->label('Versión vigente')->prefix('v'),
        ])->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlantillaFormularios::route('/'),
            'create' => CreatePlantillaFormulario::route('/create'),
            'edit' => EditPlantillaFormulario::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'Plantilla de formulario';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Plantillas de formulario';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Configuración';
    }
}
