<?php

namespace App\Filament\Resources\Sucursals\Schemas;

use App\Filament\Resources\Clientes\Schemas\ClienteForm;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SucursalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('cliente_id')
                    ->label('Cliente')
                    ->relationship('cliente', 'razon_social')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpanFull(),
                ...self::campos(),
            ]);
    }

    /** Campos compartidos entre el Resource y el RelationManager de Cliente. */
    public static function campos(): array
    {
        return [
            Section::make('Datos de la sucursal')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('nombre')->required()->maxLength(255),
                    TextInput::make('direccion')->label('Dirección')->maxLength(255),
                    TextInput::make('latitud')->numeric(),
                    TextInput::make('longitud')->numeric(),
                    Textarea::make('horarios_acceso')
                        ->label('Horarios de acceso')
                        ->columnSpanFull(),
                ]),

            Section::make('Contactos de la sucursal')
                ->columnSpanFull()
                ->schema([
                    Repeater::make('contactos')
                        ->label('')
                        ->helperText('Si lo dejas vacío, se usarán los del cliente.')
                        ->schema(ClienteForm::camposContacto())
                        ->columns(2)
                        ->defaultItems(0)
                        ->addActionLabel('Agregar contacto')
                        ->itemLabel(fn (array $state): ?string => $state['nombre'] ?? null)
                        ->collapsible(),
                ]),
        ];
    }
}
