<?php

namespace App\Filament\Resources\Equipos\Schemas;

use App\Enums\EstadoEquipo;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\KeyValue;
use Filament\Schemas\Schema;

class EquipoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('area_id')
                    ->label('Área')->relationship('area', 'nombre')->searchable()->preload()
                    ->required(),
                Select::make('tipo_equipo_id')->label('Tipo de equipo')->relationship('tipo', 'nombre')->searchable()->preload()->required(),
                Select::make('marca_id')
                    ->label('Marca')->relationship('marca', 'nombre')->searchable()->preload(),
                TextInput::make('modelo'),
                TextInput::make('serie'),
                TextInput::make('capacidad_valor')
                    ->numeric(),
                TextInput::make('capacidad_unidad'),
                Select::make('refrigerante_id')
                    ->label('Refrigerante')->relationship('refrigerante', 'nombre')->searchable()->preload(),
                TextInput::make('voltaje'),
                TextInput::make('corriente'),
                KeyValue::make('datos_placa')->label('Datos de placa'),
                DatePicker::make('fecha_instalacion'),
                DatePicker::make('garantia_hasta'),
                Select::make('estado')
                    ->options(EstadoEquipo::class)
                    ->default('operativo')
                    ->required(),
                Textarea::make('notas')
                    ->columnSpanFull(),
            ]);
    }
}
