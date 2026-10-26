<?php

namespace App\Filament\Resources\Contactos\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ContactoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->required(),
                TextInput::make('telefono')
                    ->tel()
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                TextInput::make('ciudad'),
                TextInput::make('servicio'),
                Textarea::make('mensaje')
                    ->required()
                    ->columnSpanFull(),
                Toggle::make('atendido')
                    ->required(),
                TextInput::make('ip'),
            ]);
    }
}
