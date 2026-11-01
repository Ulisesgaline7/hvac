<?php

namespace App\Filament\Resources\Sucursals\Schemas;

use App\Filament\Resources\Clientes\Schemas\ClienteForm;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Schemas\Components\Utilities\Get;
use App\Models\Sucursal;

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
                    TextInput::make('direccion')
                        ->label('Dirección')
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->suffixAction(
                            Action::make('verEnMapa')
                                ->label('Ver en Google Maps')
                                ->icon('heroicon-o-map-pin')
                                ->url(fn (Get $get): ?string => Sucursal::mapaUrl($get('direccion'), $get('latitud'), $get('longitud')))
                                ->openUrlInNewTab()
                                ->disabled(fn (Get $get): bool => blank($get('direccion')) && blank($get('latitud')))
                        ),
                    TextInput::make('latitud')->numeric()->live(onBlur: true),
                    TextInput::make('longitud')->numeric()->live(onBlur: true),
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
