<?php

namespace App\Filament\Resources\AjustesWeb;

use App\Filament\Resources\AjustesWeb\Pages\ManageAjustesWeb;
use App\Models\AjusteWeb;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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

    protected static ?string $navigationLabel = 'Textos y contacto del sitio';

    protected static ?string $modelLabel = 'ajuste';

    protected static ?string $pluralModelLabel = 'textos y contacto del sitio';

    protected static ?string $recordTitleAttribute = 'etiqueta';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('etiqueta')->label('Campo')->disabled()->dehydrated(false),
            Textarea::make('valor')->label('Contenido')->rows(4)
                ->helperText(fn (?AjusteWeb $record) => $record?->ayuda),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('grupo')->label('Sección')->badge(),
                TextColumn::make('etiqueta')->label('Campo'),
                TextColumn::make('valor')->label('Contenido')->limit(70)->placeholder('(vacío)'),
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
