<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Cliente;
use App\Models\User;
use App\Services\AccesosUsuario;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Sistema';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'usuario';

    protected static ?string $pluralModelLabel = 'usuarios';

    private static function esAdmin(): bool
    {
        return (bool) auth()->user()?->hasRole('administrador');
    }

    public static function canViewAny(): bool
    {
        return static::esAdmin();
    }

    public static function canCreate(): bool
    {
        return static::esAdmin();
    }

    public static function canEdit($record): bool
    {
        return static::esAdmin();
    }

    public static function canDelete($record): bool
    {
        return static::esAdmin();
    }

    public static function form(Schema $schema): Schema
    {
        $esCliente = fn (Get $get) => in_array($get('rol'), ['cliente_admin', 'cliente_consulta']);

        return $schema->components([
            TextInput::make('name')->label('Nombre')->required()->maxLength(255),
            TextInput::make('email')->label('Correo')->email()->required()->unique(ignoreRecord: true),
            TextInput::make('cargo')->label('Cargo'),
            Select::make('rol')->label('Rol')->required()->live()
                ->options(fn () => Role::where('name', '!=', 'tecnico')->orderBy('name')->pluck('name', 'name')->all())
                ->helperText('Los técnicos se crean en Técnicos.')
                ->afterStateHydrated(function ($component, $record) {
                    if ($record) {
                        $component->state($record->roles->first()?->name);
                    }
                }),
            Select::make('cliente_id')->label('Cliente')->searchable()
                ->options(fn () => Cliente::orderBy('razon_social')->pluck('razon_social', 'id')->all())
                ->visible($esCliente)
                ->required($esCliente),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable(),
                TextColumn::make('email')->label('Correo')->searchable(),
                TextColumn::make('roles.name')->label('Rol')->badge(),
                TextColumn::make('cliente_id')->label('Cliente')
                    ->formatStateUsing(fn ($state) => $state ? (Cliente::find($state)?->razon_social ?? '—') : '—'),
                IconColumn::make('debe_cambiar_password')->label('Clave pendiente')->boolean(),
            ])
            ->recordActions([
                EditAction::make()->hidden(fn ($record) => $record->hasRole('tecnico')),
                Action::make('suplantar')->label('Entrar como')->icon(Heroicon::OutlinedUser)->color('warning')->visible(fn ($record) => auth()->user()->hasRole('administrador') && ! session()->has('suplantador_id') && ! $record->hasRole('administrador') && $record->id !== auth()->id())->url(fn ($record) => \Illuminate\Support\Facades\URL::temporarySignedRoute('suplantar.iniciar', now()->addMinutes(30), ['user' => $record->id])),
                Action::make('reenviar')->label('Reenviar accesos')->icon(Heroicon::OutlinedEnvelope)
                    ->requiresConfirmation()
                    ->modalDescription('Se genera una contraseña nueva y se envía por correo. La anterior deja de funcionar.')
                    ->hidden(fn ($record) => $record->id === auth()->id())
                    ->action(function ($record) {
                        app(AccesosUsuario::class)->reenviar($record);
                        Notification::make()->title('Accesos enviados a ' . $record->email)->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
