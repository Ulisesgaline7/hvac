<?php

namespace App\Filament\Resources\Clientes\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClienteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del cliente')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('razon_social')
                            ->label('Razón social')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('identificacion_fiscal')
                            ->label('RTN')
                            ->maxLength(255),
                        Select::make('estado')
                            ->options(['activo' => 'Activo', 'inactivo' => 'Inactivo'])
                            ->required()
                            ->default('activo'),
                    ]),

                Section::make('Contactos')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('contactos')
                            ->label('')
                            ->schema(self::camposContacto())
                            ->columns(2)
                            ->defaultItems(0)
                            ->addActionLabel('Agregar contacto')
                            ->itemLabel(fn (array $state): ?string => $state['nombre'] ?? null)
                            ->collapsible(),
                    ]),

                Textarea::make('notas')
                    ->columnSpanFull(),
            ]);
    }

    public static function camposContacto(): array
    {
        return [
            TextInput::make('nombre')->label('Nombre')->required(),
            TextInput::make('cargo')->label('Cargo'),
            TextInput::make('telefono')->label('Teléfono')->tel(),
            TextInput::make('correo')->label('Correo')->email(),
        ];
    }
}
