<?php

namespace App\Filament\Resources\Clientes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\KeyValue;
use Filament\Schemas\Schema;

class ClienteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('razon_social')
                    ->required(),
                TextInput::make('identificacion_fiscal'),
                TextInput::make('estado')
                    ->required()
                    ->default('activo'),
                KeyValue::make('contactos')->label('Contactos'),
                Textarea::make('notas')
                    ->columnSpanFull(),
            ]);
    }
}
