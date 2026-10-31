<?php

namespace App\Filament\Resources\AjustesWeb;

use App\Filament\Resources\AjustesWeb\Pages\ManageAjustesWeb;
use App\Models\AjusteWeb;
use App\Support\SitioWeb;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class AjusteWebResource extends Resource
{
    protected static ?string $model = AjusteWeb::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Sitio web';

    protected static ?string $navigationLabel = 'Identidad, textos y contacto';

    protected static ?string $modelLabel = 'ajuste';

    protected static ?string $pluralModelLabel = 'identidad, textos y contacto del sitio';

    protected static ?string $recordTitleAttribute = 'etiqueta';

    public static function canCreate(): bool
    {
        return false;
    }

    protected static function esTipo(string ...$tipos): \Closure
    {
        return fn (?AjusteWeb $record): bool => in_array($record?->tipo ?? 'texto', $tipos, true);
    }

    public static function form(Schema $schema): Schema
    {
        $especiales = ['imagen', 'tema', 'color', 'si_no'];

        return $schema->components([
            TextInput::make('etiqueta')->label('Campo')->disabled()->dehydrated(false),

            Textarea::make('valor')->label('Contenido')->rows(4)
                ->visible(fn (?AjusteWeb $record): bool => ! in_array($record?->tipo ?? 'texto', $especiales, true))
                ->helperText(fn (?AjusteWeb $record) => $record?->ayuda),

            FileUpload::make('valor')->label('Imagen')->image()
                ->disk('public')->directory('sitio')->visibility('public')->maxSize(3072)
                ->visible(static::esTipo('imagen'))
                ->helperText(fn (?AjusteWeb $record) => $record?->ayuda),

            Select::make('valor')->label('Diseño del sitio')->options(SitioWeb::TEMAS)->required()
                ->visible(static::esTipo('tema'))
                ->helperText(fn (?AjusteWeb $record) => $record?->ayuda),

            ColorPicker::make('valor')->label('Color')->regex('/^#[0-9a-fA-F]{6}$/')
                ->visible(static::esTipo('color'))
                ->helperText(fn (?AjusteWeb $record) => $record?->ayuda),

            Toggle::make('valor')->label('Activado')
                ->formatStateUsing(fn ($state) => $state === '1' || $state === 1 || $state === true)
                ->dehydrateStateUsing(fn ($state) => $state ? '1' : '0')
                ->visible(static::esTipo('si_no'))
                ->helperText(fn (?AjusteWeb $record) => $record?->ayuda),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('grupo')->label('Sección')->badge(),
                TextColumn::make('etiqueta')->label('Campo'),
                TextColumn::make('valor')->label('Contenido')->limit(70)->placeholder('(vacío)')
                    ->formatStateUsing(fn ($state, AjusteWeb $record) => match ($record->tipo ?? 'texto') {
                        'imagen' => filled($state) ? 'Imagen: ' . basename($state) : $state,
                        'tema' => SitioWeb::TEMAS[$state] ?? $state,
                        'si_no' => $state === '1' ? 'Sí' : 'No',
                        default => $state,
                    }),
            ])
            ->defaultSort('orden')
            ->paginated(false)
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageAjustesWeb::route('/')];
    }
}
