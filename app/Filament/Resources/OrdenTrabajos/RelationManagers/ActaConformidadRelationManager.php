<?php

namespace App\Filament\Resources\OrdenTrabajos\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ActaConformidadRelationManager extends RelationManager
{
    protected static string $relationship = 'actas';

    protected static ?string $title = 'Acta de conformidad';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('fecha')->label('Fecha')->default(now())->required(),
            CheckboxList::make('confirmaciones')->label('El cliente confirma que')->options([
                'servicio_realizado' => 'El servicio descrito fue realizado en su presencia o en la de su representante.',
                'recibio_reporte' => 'Recibió el reporte de servicio y las recomendaciones del técnico.',
                'equipos_funcionando' => 'Los equipos quedaron funcionando, salvo lo indicado en las observaciones.',
                'recibio_garantia' => 'Recibió el certificado de garantía del trabajo.',
            ])->columnSpanFull(),
            Textarea::make('observaciones_cliente')->label('Observaciones del cliente')->columnSpanFull(),
            Select::make('calificacion')->label('Calificación del servicio')->options([
                1 => '1 · Muy malo', 2 => '2', 3 => '3', 4 => '4', 5 => '5 · Excelente',
            ]),
            TextInput::make('recibe_nombre')->label('Recibe (nombre)')->required(),
            TextInput::make('recibe_cargo')->label('Cargo'),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('recibe_nombre')
            ->columns([
                TextColumn::make('fecha')->date('d/m/Y')->label('Fecha'),
                TextColumn::make('recibe_nombre')->label('Recibe'),
                TextColumn::make('calificacion')->label('Calificación')->placeholder('—'),
            ])
            ->headerActions([CreateAction::make()->label('Registrar acta')])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
