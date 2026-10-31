<?php

namespace App\Filament\Resources\PaginasWeb;

use App\Filament\Resources\PaginasWeb\Pages\CreatePaginaWeb;
use App\Filament\Resources\PaginasWeb\Pages\EditPaginaWeb;
use App\Filament\Resources\PaginasWeb\Pages\ListPaginasWeb;
use App\Filament\Resources\PaginasWeb\Schemas\BloquesWeb;
use App\Models\WebPagina;
use App\Support\PlantillasWeb;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
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
            Section::make('Datos de la página')->columns(2)->schema([
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
                Select::make('plantilla')->label('Empezar desde una plantilla')
                    ->options(PlantillasWeb::opciones())->placeholder('En blanco')
                    ->live()->dehydrated(false)->visibleOn('create')->columnSpanFull()
                    ->helperText('Rellena las secciones con un ejemplo que luego editas. Reemplaza lo que ya haya.')
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('bloques', PlantillasWeb::conIds($state))),
                Toggle::make('publicada')->label('Publicada')
                    ->helperText('Apagada = solo la ves tú desde el panel (vista previa).'),
                Toggle::make('mostrar_titulo')->label('Mostrar el título arriba')->default(true)
                    ->helperText('Apágalo si la primera sección ya trae su propio título grande.'),
                Toggle::make('es_inicio')->label('Es la portada del sitio')
                    ->helperText('Si está publicada, se muestra en «/». Solo una página puede serlo.'),
            ]),

            Section::make('Secciones')->description('Agrega, ordena y edita las secciones. Se ven en el mismo orden en el sitio.')
                ->schema([BloquesWeb::constructor()]),

            Section::make('Portada y Google (SEO)')->collapsed()->schema([
                FileUpload::make('imagen')->label('Imagen de portada / para compartir (opcional)')->image()
                    ->disk('public')->directory('paginas')->visibility('public')->maxSize(4096),
                TextInput::make('meta_titulo')->label('Título para Google')->maxLength(160)
                    ->helperText('Vacío = se usa el título de la página.'),
                Textarea::make('meta_descripcion')->label('Descripción para Google')->rows(2)->maxLength(300)
                    ->helperText('Vacío = se toma del inicio del contenido.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('titulo')->label('Título')->searchable()->wrap(),
                TextColumn::make('slug')->label('Dirección')->formatStateUsing(fn ($state) => '/' . $state)->copyable(),
                IconColumn::make('es_inicio')->label('Portada')->boolean(),
                ToggleColumn::make('publicada')->label('Publicada'),
                TextColumn::make('updated_at')->label('Actualizada')->dateTime('d/m/Y H:i')->toggleable(),
            ])
            ->defaultSort('orden')
            ->reorderable('orden')
            ->recordActions([
                Action::make('ver')->label(fn (WebPagina $record) => $record->publicada ? 'Ver' : 'Vista previa')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (WebPagina $record) => $record->es_inicio && $record->publicada ? url('/') : url('/' . $record->slug))
                    ->openUrlInNewTab(),
                EditAction::make(),
                Action::make('duplicar')->label('Duplicar')->icon('heroicon-o-document-duplicate')
                    ->requiresConfirmation()->modalDescription('Se crea una copia sin publicar.')
                    ->action(function (WebPagina $record) {
                        $base = $record->slug . '-copia';
                        $slug = $base;
                        $n = 2;
                        while (WebPagina::query()->where('slug', $slug)->exists()) {
                            $slug = $base . '-' . $n++;
                        }

                        $copia = $record->replicate();
                        $copia->titulo = $record->titulo . ' (copia)';
                        $copia->slug = $slug;
                        $copia->publicada = false;
                        $copia->es_inicio = false;
                        $copia->orden = 0;
                        $copia->save();

                        Notification::make()->title('Página duplicada')->body('Quedó sin publicar: /' . $slug)->success()->send();
                    }),
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
