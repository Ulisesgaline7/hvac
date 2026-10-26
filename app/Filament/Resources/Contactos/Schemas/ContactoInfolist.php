<?php

namespace App\Filament\Resources\Contactos\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ContactoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('nombre'),
                TextEntry::make('telefono'),
                TextEntry::make('email')
                    ->label('Email address')
                    ->placeholder('-'),
                TextEntry::make('ciudad')
                    ->placeholder('-'),
                TextEntry::make('servicio')
                    ->placeholder('-'),
                TextEntry::make('mensaje')
                    ->columnSpanFull(),
                IconEntry::make('atendido')
                    ->boolean(),
                TextEntry::make('ip')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
