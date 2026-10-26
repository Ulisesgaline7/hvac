<?php

namespace App\Filament\Resources\ServiciosWeb;

use App\Filament\Resources\ServiciosWeb\Pages\CreateServicioWeb;
use App\Filament\Resources\ServiciosWeb\Pages\EditServicioWeb;
use App\Filament\Resources\ServiciosWeb\Pages\ListServiciosWeb;
use App\Models\ServicioWeb;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class ServicioWebResource extends Resource
{
    protected static ?string $model = ServicioWeb::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string|UnitEnum|null $navigationGroup = 'Sitio web';

    protected static ?string $navigationLabel = 'Servicios del sitio';

    protected static ?string $modelLabel = 'servicio';

    protected static ?string $pluralModelLabel = 'servicios del sitio';

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('nombre')->label('Nombre del servicio')->required()->maxLength(120)->columnSpanFull(),
            Textarea::make('descripcion')->label('Descripción')->required()->rows(4)->maxLength(500)->columnSpanFull(),
            TextInput::make('precio_desde')->label('Precio desde')->numeric()->minValue(0)
                ->helperText('Déjalo vacío para no mostrar precio en la web.'),
            Select::make('moneda')->label('Moneda')->options(['L' => 'Lempiras (L)', 'US$' => 'Dólares (US$)'])
                ->default('L')->required(),
            TextInput::make('unidad')->label('Unidad')->maxLength(60)
                ->placeholder('por visita, por equipo, por mes...'),
            TextInput::make('orden')->label('Orden')->numeric()->default(0),
            FileUpload::make('imagen')->label('Foto (opcional)')->image()->disk('public')->directory('servicios')
                ->visibility('public')->maxSize(5120)->columnSpanFull(),
            Toggle::make('visible')->label('Visible en la web')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('imagen')->label('Foto')->disk('public')->square(),
                TextColumn::make('nombre')->label('Servicio')->searchable()->sortable(),
                TextColumn::make('precio_desde')->label('Desde')
                    ->formatStateUsing(fn ($state, $record) => $state === null ? '—' : $record->moneda . ' ' . number_format((float) $state, 2)),
                ToggleColumn::make('visible')->label('Visible'),
            ])
            ->defaultSort('orden')
            ->reorderable('orden')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServiciosWeb::route('/'),
            'create' => CreateServicioWeb::route('/create'),
            'edit' => EditServicioWeb::route('/{record}/edit'),
        ];
    }
}
