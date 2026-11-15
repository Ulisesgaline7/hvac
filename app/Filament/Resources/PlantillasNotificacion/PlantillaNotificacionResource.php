<?php

namespace App\Filament\Resources\PlantillasNotificacion;

use App\Filament\Resources\PlantillasNotificacion\Pages\EditPlantillaNotificacion;
use App\Filament\Resources\PlantillasNotificacion\Pages\ListPlantillasNotificacion;
use App\Models\PlantillaNotificacion;
use App\Services\NotificadorTecnico;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

class PlantillaNotificacionResource extends Resource
{
    public static function canViewAny(): bool
    {
        $u = auth()->user();

        return ! ($u?->hasRole('comercial') && ! $u->hasAnyRole(['administrador', 'coordinador']));
    }

    protected static string|\UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 90;

    protected static ?string $model = PlantillaNotificacion::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bell-alert';
    protected static ?string $navigationLabel = 'Plantillas de notificaciones';
    protected static ?string $navigationParentItem = 'Documentos';
    protected static ?string $modelLabel = 'plantilla';
    protected static ?string $pluralModelLabel = 'plantillas de notificaciones';

    public static function canCreate(): bool
    {
        return false; // las claves de evento las define el código
    }

    public static function form(Schema $schema): Schema
    {
        $vars = fn (?PlantillaNotificacion $r) => 'Variables: ' . collect(PlantillaNotificacion::VARIABLES[$r?->clave] ?? [])
            ->map(fn ($v) => '{' . $v . '}')->implode('  ');

        return $schema->components([
            Section::make('Evento')->schema([
                TextInput::make('nombre')->disabled(),
                TextInput::make('clave')->disabled(),
                Textarea::make('descripcion')->label('Cuándo se envía')->disabled()->rows(2)->columnSpanFull(),
            ])->columns(2),

            Section::make('Mensaje (campana del panel y correo)')->schema([
                TextInput::make('titulo')->label('Título / asunto del correo')->required()->maxLength(255)
                    ->helperText(fn (?PlantillaNotificacion $record) => $vars($record)),
                Textarea::make('cuerpo')->label('Texto')->required()->rows(7)
                    ->helperText('Separa párrafos con una línea en blanco. ' . 'Usa las variables entre llaves.'),
            ]),

            Section::make('Push (pantalla bloqueada)')
                ->description('Se ve sin abrir el teléfono: evita poner montos si no quieres que se lean ahí.')
                ->schema([
                    TextInput::make('push_titulo')->label('Título push')->required()->maxLength(255),
                    TextInput::make('push_cuerpo')->label('Texto push')->required()->maxLength(255),
                ])->columns(2),

            Section::make('Envío')->schema([
                Toggle::make('activa')->label('Plantilla activa'),
                Toggle::make('correo_activo')->label('Enviar correo'),
                Toggle::make('push_activo')->label('Enviar push'),
                Toggle::make('avisar_tecnico')->label('Avisar al técnico'),
                Select::make('roles_extra')->label('Avisar también a estos roles')
                    ->multiple()->options(fn () => Role::query()->pluck('name', 'name')->all())
                    ->columnSpanFull(),
            ])->columns(4),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id')
            ->columns([
                TextColumn::make('nombre')->searchable(),
                TextColumn::make('clave')->badge()->color('gray'),
                IconColumn::make('correo_activo')->label('Correo')->boolean(),
                IconColumn::make('push_activo')->label('Push')->boolean(),
                IconColumn::make('avisar_tecnico')->label('Técnico')->boolean(),
                IconColumn::make('activa')->label('Activa')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('probar')->label('Enviarme una prueba')->icon('heroicon-o-paper-airplane')
                    ->action(function (PlantillaNotificacion $record) {
                        NotificadorTecnico::prueba($record, auth()->user());
                        Notification::make()->title('Prueba enviada a tu usuario')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlantillasNotificacion::route('/'),
            'edit' => EditPlantillaNotificacion::route('/{record}/edit'),
        ];
    }
}
