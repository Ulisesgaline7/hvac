<?php

namespace App\Filament\Resources\DocumentosTerceros;

use App\Filament\Resources\DocumentosTerceros\Pages\CreateDocumentoTercero;
use App\Filament\Resources\DocumentosTerceros\Pages\EditDocumentoTercero;
use App\Filament\Resources\DocumentosTerceros\Pages\ListDocumentosTerceros;
use App\Models\DocumentoTercero;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DocumentoTerceroResource extends Resource
{
    public static function canViewAny(): bool
    {
        $u = auth()->user();

        return ! ($u?->hasRole('comercial') && ! $u->hasAnyRole(['administrador', 'coordinador']));
    }

    protected static string|\UnitEnum|null $navigationGroup = 'Documentos';

    protected static ?int $navigationSort = 3;

    protected static ?string $model = DocumentoTercero::class;
    protected static ?string $modelLabel = 'documento de tercero';
    protected static ?string $pluralModelLabel = 'documentos de terceros';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperClip;
    protected static ?string $slug = 'documentos-terceros';

    private static function esGestor(): bool
    {
        return auth()->user()?->hasAnyRole(['administrador', 'coordinador']) ?? false;
    }

    public static function canCreate(): bool
    {
        return self::esGestor();
    }

    public static function canEdit($record): bool
    {
        return self::esGestor();
    }

    public static function canDelete($record): bool
    {
        return self::esGestor();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Documento')->columns(2)->columnSpanFull()->schema([
                Select::make('tipo')->options(DocumentoTercero::TIPOS)->required()->native(false),
                TextInput::make('titulo')->label('Título')->required(),
                TextInput::make('emisor')->label('Fabricante, proveedor o gestor que lo emite'),
                Select::make('proveedor_id')->label('Proveedor registrado')->relationship('proveedor', 'nombre')->searchable()->preload(),
                DatePicker::make('emitido_en')->label('Fecha de emisión'),
                DatePicker::make('vigente_hasta')->label('Vigente hasta')->helperText('Solo si el documento trae vencimiento.'),
                DatePicker::make('revisar_en')->label('Revisar en')
                    ->helperText('En FDS y fichas técnicas se calcula sola a 2 años de la emisión.'),
                \Filament\Forms\Components\FileUpload::make('archivo_ruta')->label('Archivo')
                    ->disk('local')->directory('documentos-terceros')->visibility('private')
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])->maxSize(20480)
                    ->storeFileNamesIn('archivo_nombre')->columnSpanFull()
                    ->helperText('PDF o imagen, hasta 20 MB. Se conserva tal cual lo entregó el tercero.'),
                Textarea::make('notas')->columnSpanFull(),
            ]),
            Section::make('A qué se aplica')->description('Elige lo que corresponda; pueden ser varios.')->columns(2)->columnSpanFull()->schema([
                Select::make('refrigerante_id')->label('Refrigerante')->relationship('refrigerante', 'nombre')->searchable()->preload(),
                Select::make('cilindro_id')->label('Cilindro')->relationship('cilindro', 'codigo')->searchable()->preload(),
                Select::make('insumo_id')->label('Insumo')->relationship('insumo', 'nombre')->searchable()->preload(),
                Select::make('equipo_id')->label('Equipo')->relationship('equipo', 'codigo')->searchable(),
                Select::make('orden_trabajo_id')->label('Orden de trabajo')->relationship('ordenTrabajo', 'numero')->searchable(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('tipo')->badge()->formatStateUsing(fn ($state) => DocumentoTercero::TIPOS[$state] ?? $state),
                TextColumn::make('titulo')->searchable()->wrap(),
                TextColumn::make('emisor')->placeholder('—'),
                TextColumn::make('aplica')->label('Aplica a')->state(fn ($record) => $record->aplicaA())->wrap(),
                TextColumn::make('vigente_hasta')->label('Vigente hasta')->date('d/m/Y')->placeholder('—')
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : null),
                TextColumn::make('revisar_en')->label('Revisar')->date('d/m/Y')->placeholder('—')
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : null),
            ])
            ->filters([
                SelectFilter::make('tipo')->options(DocumentoTercero::TIPOS),
                Filter::make('vencidos')->label('Vencidos o por revisar')->query(fn (Builder $q) => $q->vencidos()),
                Filter::make('por_vencer')->label('Vencen en 30 días')->query(fn (Builder $q) => $q->porVencer(30)),
            ])
            ->recordActions([
                \Filament\Actions\Action::make('abrir')->label('Abrir')->icon('heroicon-o-arrow-top-right-on-square')
                    ->visible(fn ($record) => filled($record->archivo_ruta))
                    ->url(fn ($record) => route('documentos-terceros.archivo', $record), shouldOpenInNewTab: true),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocumentosTerceros::route('/'),
            'create' => CreateDocumentoTercero::route('/create'),
            'edit' => EditDocumentoTercero::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
