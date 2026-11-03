<?php

namespace App\Filament\Resources\BibliotecaDocumentos;

use App\Filament\Resources\BibliotecaDocumentos\Pages\CreateDocumento;
use App\Filament\Resources\BibliotecaDocumentos\Pages\EditDocumento;
use App\Filament\Resources\BibliotecaDocumentos\Pages\ListDocumentos;
use App\Filament\Resources\BibliotecaDocumentos\RelationManagers\VersionesRelationManager;
use App\Models\DocumentoCatalogo;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class BibliotecaDocumentoResource extends Resource
{
    protected static ?string $model = DocumentoCatalogo::class;

    protected static ?string $slug = 'biblioteca-documentos';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Biblioteca de documentos';

    protected static ?string $modelLabel = 'documento';

    protected static ?string $pluralModelLabel = 'documentos';

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['administrador', 'coordinador']) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole('administrador') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->hasRole('administrador') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identificación')->columns(2)->columnSpanFull()->schema([
                TextInput::make('codigo')->label('Código del catálogo')->required()->unique(ignoreRecord: true),
                TextInput::make('codigo_interno')->label('Código interno (ej. PRO-TEC-01)'),
                TextInput::make('nombre')->required()->columnSpanFull(),
                TextInput::make('grupo'),
                Select::make('diseno')->label('Diseño')->options(DocumentoCatalogo::DISENOS),
                Select::make('estado')->options(DocumentoCatalogo::ESTADOS)->required()->default('pendiente'),
            ]),

            Section::make('Control del documento')->columns(3)->columnSpanFull()->schema([
                TextInput::make('version')->label('Versión')->required()->default('1.0'),
                DatePicker::make('vigente_desde')->label('Vigente desde'),
                DatePicker::make('proxima_revision')->label('Próxima revisión'),
                TextInput::make('elaborado_por')->label('Elaboró'),
                TextInput::make('revisado_por')->label('Revisó'),
                TextInput::make('aprobado_por')->label('Aprobó'),
            ]),

            Section::make('Contenido')->columnSpanFull()->schema([
                RichEditor::make('contenido')->label('')->columnSpanFull(),
            ]),

            Section::make('Origen en el sistema')->columns(2)->columnSpanFull()->collapsed()->schema([
                TextInput::make('archivo'),
                TextInput::make('modulo'),
                TextInput::make('requisito'),
                TextInput::make('tabla_propuesta'),
                Textarea::make('notas')->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('codigo')
            ->paginated([25, 50, 100])
            ->columns([
                TextColumn::make('codigo')->label('Código')->searchable()->sortable(),
                TextColumn::make('codigo_interno')->label('Interno')->searchable()->toggleable(),
                TextColumn::make('nombre')->searchable()->wrap(),
                TextColumn::make('grupo')->badge()->toggleable(),
                TextColumn::make('diseno')->label('Diseño')->badge()
                    ->formatStateUsing(fn ($state) => DocumentoCatalogo::DISENOS[$state] ?? '—'),
                TextColumn::make('estado')->badge()
                    ->color(fn ($state) => match ($state) {
                        'vigente' => 'success',
                        'en_progreso' => 'warning',
                        'obsoleto', 'fuera' => 'gray',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn ($state) => DocumentoCatalogo::ESTADOS[$state] ?? $state),
                TextColumn::make('version')->label('Versión'),
                IconColumn::make('con_contenido')->label('Contenido')->boolean()
                    ->getStateUsing(fn ($record) => filled($record->contenido)),
            ])
            ->filters([
                SelectFilter::make('grupo')
                    ->options(fn () => DocumentoCatalogo::query()->whereNotNull('grupo')->distinct()->orderBy('grupo')->pluck('grupo', 'grupo')->all()),
                SelectFilter::make('diseno')->label('Diseño')->options(DocumentoCatalogo::DISENOS),
                SelectFilter::make('estado')->options(DocumentoCatalogo::ESTADOS),
                Filter::make('sin_contenido')->label('Sin contenido')->toggle()
                    ->query(fn (Builder $q) => $q->where(fn ($w) => $w->whereNull('contenido')->orWhere('contenido', ''))),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('pdf')->label('PDF')->icon('heroicon-o-document-arrow-down')
                    ->url(fn ($record) => route('documentos.pdf', $record))
                    ->openUrlInNewTab(),
            ]);
    }

    public static function getRelations(): array
    {
        return [VersionesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocumentos::route('/'),
            'create' => CreateDocumento::route('/create'),
            'edit' => EditDocumento::route('/{record}/edit'),
        ];
    }
}
