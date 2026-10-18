<?php

namespace App\Filament\Resources\Sucursals\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\KeyValue;
use Filament\Schemas\Schema;

class SucursalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('cliente_id')
                    ->label('Cliente')->relationship('cliente', 'razon_social')->searchable()->preload()
                    ->required(),
                TextInput::make('nombre')
                    ->required(),
                TextInput::make('direccion'),
                TextInput::make('latitud')
                    ->numeric(),
                TextInput::make('longitud')
                    ->numeric(),
                Textarea::make('horarios_acceso')
                    ->columnSpanFull(),
                KeyValue::make('contactos')->label('Contactos'),
            ]);
    }
}
