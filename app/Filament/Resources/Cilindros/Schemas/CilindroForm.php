<?php

namespace App\Filament\Resources\Cilindros\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CilindroForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('codigo')->label('Código del cilindro')->required()->unique(ignoreRecord: true),
            Select::make('refrigerante_id')->label('Refrigerante')->relationship('refrigerante', 'nombre')->required(),
            TextInput::make('peso_inicial_kg')->label('Peso inicial de refrigerante')->numeric()->minValue(0.01)->suffix('kg')->required(),
            Select::make('estado')->options(['activo' => 'Activo', 'vacio' => 'Vacío', 'retirado' => 'Retirado'])->default('activo')->required(),
            TextInput::make('lote')->label('Lote del refrigerante')->helperText('Está en la etiqueta del cilindro.'),
            Select::make('proveedor_id')->label('Proveedor')->relationship('proveedor', 'nombre')->searchable()->preload(),
            DatePicker::make('recibido_en')->label('Fecha de recepción'),
            Textarea::make('notas')->columnSpanFull(),
        ]);
    }
}
