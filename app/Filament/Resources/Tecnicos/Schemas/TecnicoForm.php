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
                ])
                ->createOptionUsing(function (array $data): int {
                    $usuario = app(\App\Services\AccesosUsuario::class)->crear($data, 'tecnico');

                    return $usuario->getKey();
                }),
            TextInput::make('telefono')->label('Teléfono')->tel(),
            TextInput::make('rtn')->label('RTN'),
            Select::make('nivel')->label('Nivel')
                ->options(['nivel_1' => 'Técnico nivel 1', 'nivel_2' => 'Técnico nivel 2', 'nivel_3' => 'Técnico nivel 3', 'lider' => 'Técnico líder']),
            Select::make('modalidad')->label('Modalidad')
                ->options(['individual' => 'Individual', 'cuadrilla' => 'Cuadrilla', 'especialista' => 'Especialista', 'respaldo' => 'Respaldo', 'emergencia' => 'Emergencia'])
                ->default('individual')->required(),
            TextInput::make('retencion_pct')->label('Retención (%)')->numeric()->minValue(0)->maxValue(100)
                ->helperText('Vacío = sin retención. Lo define el contador para cada técnico.'),
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
                    'cocina_industrial' => 'Cocina industrial',
                    'electrica' => 'Eléctrica',
                ])
                ->columns(2)
                ->columnSpanFull(),
            Textarea::make('notas')->columnSpanFull(),
        ]);
    }
}
