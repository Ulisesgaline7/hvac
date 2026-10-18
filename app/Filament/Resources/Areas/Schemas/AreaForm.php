<?php

namespace App\Filament\Resources\Areas\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AreaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('sucursal_id')
                    ->label('Sucursal')->relationship('sucursal', 'nombre')->searchable()->preload()
                    ->required(),
                TextInput::make('nombre')
                    ->required(),
            ]);
    }
}
