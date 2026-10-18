<?php

namespace App\Filament\Resources\OrdenTrabajos\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TrabajosAdicionalesRelationManager extends RelationManager
{
    protected static string $relationship = 'trabajosAdicionales';

    protected static ?string $title = 'Trabajos adicionales';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('fecha')->label('Fecha')->default(now())->required(),
            Select::make('orden_trabajo_equipo_id')->label('Equipo')
                ->options(fn () => $this->getOwnerRecord()->lineas()->with('equipo')->get()
                    ->mapWithKeys(fn ($l) => [$l->id => $l->equipo?->codigo ?? 'Equipo por instalar'])),
            Textarea::make('hallazgo')->label('Hallazgo (qué se encontró y por qué se necesita)')->required()->columnSpanFull(),
            Textarea::make('trabajo_propuesto')->label('Trabajo propuesto')->required()->columnSpanFull(),
            Textarea::make('consecuencias')->label('Consecuencias si no se realiza')->columnSpanFull(),
            Repeater::make('detalle_costo')->label('Costo (sin ISV)')->columnSpanFull()
                ->columns(3)
                ->schema([
                    TextInput::make('concepto')->label('Concepto')->required(),
                    TextInput::make('cantidad')->label('Cantidad')->numeric()->default(1)->minValue(0)->required(),
                    TextInput::make('precio_unitario')->label('Precio unit. L')->numeric()->minValue(0)->required(),
                ])
                ->helperText('El subtotal, el ISV (15 %) y el total se calculan al guardar.'),
            TextInput::make('tiempo_adicional')->label('Tiempo adicional'),
            DatePicker::make('nueva_fecha')->label('Nueva fecha'),
            Select::make('decision')->label('Decisión del cliente')->options([
                'pendiente' => 'Pendiente',
                'aprobado' => 'Apruebo el trabajo y el costo indicado',
                'rechazado' => 'No apruebo, entiendo las consecuencias',
            ])->default('pendiente')->required()->columnSpanFull(),
            TextInput::make('decidido_por_nombre')->label('Nombre de quien decide'),
            TextInput::make('decidido_por_cargo')->label('Cargo'),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('hallazgo')
            ->columns([
                TextColumn::make('fecha')->date('d/m/Y')->label('Fecha'),
                TextColumn::make('linea.equipo.codigo')->label('Equipo')->placeholder('—'),
                TextColumn::make('hallazgo')->label('Hallazgo')->limit(40),
                TextColumn::make('total')->label('Total (L)')->numeric(2),
                TextColumn::make('decision')->label('Decisión')->badge()
                    ->color(fn ($state) => match ($state) { 'aprobado' => 'success', 'rechazado' => 'danger', default => 'warning' }),
            ])
            ->headerActions([CreateAction::make()->label('Nuevo trabajo adicional')])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
