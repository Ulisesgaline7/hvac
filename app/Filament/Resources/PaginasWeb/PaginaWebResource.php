<?php

namespace App\Filament\Resources\PaginasWeb;

use App\Filament\Resources\PaginasWeb\Pages\CreatePaginaWeb;
use App\Filament\Resources\PaginasWeb\Pages\EditPaginaWeb;
use App\Filament\Resources\PaginasWeb\Pages\ListPaginasWeb;
use App\Models\WebPagina;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class PaginaWebResource extends Resource
{
    protected static ?string $model = WebPagina::class;

    protected static ?string $slug = 'paginas-web';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Sitio web';

    protected static ?string $navigationLabel = 'Páginas';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'página';

    protected static ?string $pluralModelLabel = 'páginas del sitio';

    protected static ?string $recordTitleAttribute = 'titulo';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Contenido')->schema([
                TextInput::make('titulo')->label('Título')->required()->maxLength(160)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state, string $operation) => $operation === 'create'
                        ? $set('slug', Str::slug((string) $state)) : null),
                TextInput::make('slug')->label('Dirección (URL)')->required()->maxLength(120)->prefix('/')
                    ->unique(ignoreRecord: true)
                    ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                    ->notIn(WebPagina::RESERVADOS)
                    ->validationMessages([
                        'regex' => 'Solo minúsculas, números y guiones, sin espacios ni tildes.',
                        'not_in' => 'Esa dirección la usa el sistema. Elige otra.',
                    ])
                    ->helperText('Ejemplo: «nosotros» se verá en tusitio.com/nosotros'),
                FileUpload::make('imagen')->label('Imagen de portada (opcional)')->image()
                    ->disk('public')->directory('paginas')->visibility('public')->maxSize(4096),
                RichEditor::make('contenido')->label('Contenido')
                    ->fileAttachmentsDisk('public')->fileAttachmentsDirectory('paginas-contenido')->fileAttachmentsVisibility('public')
                    ->columnSpanFull(),
            ])->columns(1),
            Section::make('Google y redes (SEO)')->collapsed()->schema([
                TextInput::make('meta_titulo')->label('Título para Google')->maxLength(160)
                    ->helperText('Vacío = se usa el título de la página.'),
                Textarea::make('meta_descripcion')->label('Descripción para Google')->rows(2)->maxLength(300)
                    ->helperText('Una o dos frases. Vacío = se toma del inicio del contenido.'),
            ]),
            Toggle::make('publicada')->label('Publicada')
                ->helperText('Mientras esté apagada nadie puede ver la página.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('titulo')->label('Título')->searchable()->wrap(),
                TextColumn::make('slug')->label('Dirección')->formatStateUsing(fn ($state) => '/' . $state)->copyable(),
                ToggleColumn::make('publicada')->label('Publicada'),
                TextColumn::make('updated_at')->label('Actualizada')->dateTime('d/m/Y H:i')->toggleable(),
            ])
            ->defaultSort('orden')
            ->reorderable('orden')
            ->recordActions([
                Action::make('ver')->label('Ver')->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (WebPagina $record) => url('/' . $record->slug))->openUrlInNewTab()
                    ->visible(fn (WebPagina $record) => $record->publicada),
                EditAction::make(),
                DeleteAction::make()->modalDescription('También se quitará de los menús donde esté.'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPaginasWeb::route('/'),
            'create' => CreatePaginaWeb::route('/create'),
            'edit' => EditPaginaWeb::route('/{record}/edit'),
        ];
    }
}
