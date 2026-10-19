<?php

namespace App\Filament\Resources\Clientes\Pages;

use App\Filament\Resources\Clientes\ClienteResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditCliente extends EditRecord
{
    protected static string $resource = ClienteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('crearAccesoPortal')
                ->label('Crear acceso al portal')
                ->icon('heroicon-o-key')
                ->color('success')
                ->visible(fn () => auth()->user()?->hasAnyRole(['administrador', 'coordinador']) ?? false)
                ->modalDescription('Crea un usuario que solo verá la información de este cliente en /portal.')
                ->schema([
                    \Filament\Forms\Components\TextInput::make('name')->label('Nombre')->required()->maxLength(255),
                    \Filament\Forms\Components\TextInput::make('email')->label('Correo')->email()->required()->maxLength(255)
                        ->rules([fn () => function (string $attribute, $value, \Closure $fail) { if (\App\Models\User::where('email', $value)->exists()) { $fail('Ese correo ya está registrado.'); } }]),
                    \Filament\Forms\Components\TextInput::make('password')->label('Contraseña inicial')->password()->revealable()->required()->minLength(8),
                    \Filament\Forms\Components\Select::make('rol')->label('Permisos')->required()->default('cliente_admin')
                        ->options(['cliente_admin' => 'Administrador del cliente', 'cliente_consulta' => 'Solo consulta']),
                ])
                ->action(function (array $data) {
                    $rol = in_array($data['rol'], ['cliente_admin', 'cliente_consulta'], true) ? $data['rol'] : 'cliente_consulta';

                    $u = \App\Models\User::create([
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'password' => bcrypt($data['password']),
                    ]);
                    $u->forceFill(['cliente_id' => $this->record->getKey()])->save();
                    $u->assignRole($rol);

                    \Filament\Notifications\Notification::make()->success()
                        ->title('Acceso creado')
                        ->body("{$u->email} entra en /portal con la contraseña que acabas de definir.")
                        ->send();
                }),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
