<?php

namespace App\Filament\Resources\Colaboradores\Schemas;

use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ColaboradorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Datos personales')->columns(2)->schema([
                TextInput::make('nombre')->required()->maxLength(255),
                Select::make('user_id')
                    ->label('Usuario del sistema')
                    ->helperText('Opcional. Vincula la ficha con su acceso (técnico, comercial, supervisor...).')
                    ->options(fn () => User::whereNull('cliente_id')->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->unique(ignoreRecord: true),
                TextInput::make('identidad')->label('Identidad (DNI)')->maxLength(30),
                TextInput::make('rtn')->label('RTN')->maxLength(30),
                DatePicker::make('fecha_nacimiento')->label('Fecha de nacimiento')->maxDate(now()),
                TextInput::make('telefono')->label('Teléfono')->tel(),
                TextInput::make('email_personal')->label('Correo personal')->email(),
                Select::make('estado')->options(['activo' => 'Activo', 'inactivo' => 'Inactivo'])->default('activo')->required(),
                Textarea::make('direccion')->label('Dirección')->columnSpanFull(),
            ]),
            Section::make('Contacto de emergencia')->columns(2)->schema([
                TextInput::make('contacto_emergencia')->label('Nombre'),
                TextInput::make('telefono_emergencia')->label('Teléfono')->tel(),
            ]),
            Section::make('Pago')->columns(2)->schema([
                TextInput::make('banco'),
                TextInput::make('cuenta_bancaria')->label('Cuenta bancaria'),
            ]),
            Textarea::make('notas')->columnSpanFull(),
        ]);
    }
}
