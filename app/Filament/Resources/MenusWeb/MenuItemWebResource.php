<?php

namespace App\Filament\Resources\MenusWeb;

use App\Filament\Resources\MenusWeb\Pages\ManageMenusWeb;
use App\Models\MenuItemWeb;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class MenuItemWebResource extends Resource
{
    protected static ?string $model = MenuItemWeb::class;

    protected static ?string $slug = 'menus-web';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3;

    protected static string|UnitEnum|null $navigationGroup = 'Sitio web';

    protected static ?string $navigationLabel = 'Menús';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'elemento del menú';

    protected static ?string $pluralModelLabel = 'menús del sitio';

    protected static ?string $recordTitleAttribute = 'etiqueta';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('ubicacion')->label('Menú')->required()->default('principal')->live()
                ->options(['principal' => 'Menú superior', 'pie' => 'Pie de página']),
            TextInput::make('etiqueta')->label('Texto que se ve')->required()->maxLength(80),
            Select::make('tipo')->label('Lleva a')->required()->default('pagina')->live()
                ->options(['pagina' => 'Una página del sitio', 'url' => 'Un enlace o sección']),
            Select::make('pagina_id')->label('Página')->relationship('pagina', 'titulo')
                ->searchable()->preload()
                ->visible(fn (Get $get) => $get('tipo') === 'pagina')
                ->required(fn (Get $get) => $get('tipo') === 'pagina')
                ->helperText('Si la página no está publicada, el enlace no se muestra.'),
            TextInput::make('url')->label('Enlace')->maxLength(255)
                ->visible(fn (Get $get) => $get('tipo') === 'url')
                ->required(fn (Get $get) => $get('tipo') === 'url')
                ->regex('/^(https?:\/\/|\/|#|mailto:|tel:)/i')
                ->validationMessages(['regex' => 'Debe empezar con https://, /, #, mailto: o tel:'])
                ->helperText('Ejemplos: https://facebook.com/tu-pagina · /#contacto (sección de la portada) · tel:+50499999999 · mailto:info@tusitio.com'),
            Select::make('parent_id')->label('Dentro de (submenú)')->placeholder('Ninguno: nivel principal')
                ->options(fn (Get $get, ?MenuItemWeb $record) => MenuItemWeb::query()
                    ->whereNull('parent_id')
                    ->where('ubicacion', $get('ubicacion') ?: 'principal')
                    ->when($record, fn (Builder $query) => $query->whereKeyNot($record->id))
                    ->orderBy('orden')->pluck('etiqueta', 'id')->all())
                ->visible(fn (Get $get) => $get('ubicacion') === 'principal')
                ->disabled(fn (?MenuItemWeb $record) => $record ? $record->hijos()->exists() : false)
                ->helperText('Solo un nivel. Un elemento que ya tiene submenú no puede quedar dentro de otro.'),
            Toggle::make('boton')->label('Mostrar como botón')
                ->visible(fn (Get $get) => $get('ubicacion') === 'principal'),
            Toggle::make('nueva_pestana')->label('Abrir en una pestaña nueva'),
            Toggle::make('visible')->label('Visible')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['pagina', 'padre']))
            ->columns([
                TextColumn::make('etiqueta')->label('Texto')->searchable(),
                TextColumn::make('destino')->label('Lleva a')
                    ->getStateUsing(fn (MenuItemWeb $record) => $record->tipo === 'pagina'
                        ? 'Página: ' . ($record->pagina?->titulo ?? '(eliminada)')
                        : $record->url)
                    ->wrap(),
                TextColumn::make('padre.etiqueta')->label('Submenú de')->badge()->placeholder('—'),
                IconColumn::make('boton')->label('Botón')->boolean(),
                ToggleColumn::make('visible')->label('Visible'),
            ])
            ->defaultSort('orden')
            ->reorderable('orden')
            ->paginated(false)
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageMenusWeb::route('/')];
    }
}
