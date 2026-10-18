<?php

namespace App\Filament\Resources\TipoEquipos\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TipoEquipoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->required(),
                TextInput::make('familia')
                    ->required(),
                TextInput::make('prefijo_codigo')
                    ->required(),
                TextInput::make('plantilla_id')
                    ->numeric(),
            ]);
    }
}
