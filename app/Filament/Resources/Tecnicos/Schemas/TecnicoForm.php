<?php

namespace App\Filament\Resources\Tecnicos\Schemas;

use App\Models\User;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class TecnicoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')
                ->label('Usuario')
                ->options(fn () => User::role('tecnico')->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->required()
                ->unique(ignoreRecord: true)
                ->createOptionForm([
                    TextInput::make('name')->label('Nombre')->required(),
                    TextInput::make('email')->label('Correo')->email()->required()->unique('users', 'email'),
                    TextInput::make('password')->label('Contraseña')->password()->required()->minLength(8),
                ])
                ->createOptionUsing(function (array $data): int {
                    $usuario = User::create($data);
                    $usuario->assignRole('tecnico');

                    return $usuario->getKey();
                }),
            TextInput::make('telefono')->label('Teléfono')->tel(),
            TextInput::make('zona')->label('Zona de trabajo'),
            Select::make('estado_homologacion')
                ->label('Homologación')
                ->options(['pendiente' => 'Pendiente', 'aprobado' => 'Aprobado', 'suspendido' => 'Suspendido'])
                ->default('pendiente')
                ->required(),
            CheckboxList::make('especialidades')
                ->options([
                    'aire_acondicionado' => 'Aire acondicionado',
                    'refrigeracion_comercial' => 'Refrigeración comercial',
                    'cuartos_frios' => 'Cuartos fríos',
                    'electrica' => 'Eléctrica',
                ])
                ->columns(2)
                ->columnSpanFull(),
            Textarea::make('notas')->columnSpanFull(),
        ]);
    }
}
