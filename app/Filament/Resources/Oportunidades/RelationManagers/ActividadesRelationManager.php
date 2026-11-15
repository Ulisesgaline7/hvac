<?php

namespace App\Filament\Resources\Oportunidades\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ActividadesRelationManager extends RelationManager
{
    protected static string $relationship = 'actividades';
    protected static ?string $title = 'Seguimiento';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('tipo')
                ->options([
                    'nota' => 'Nota',
                    'llamada' => 'Llamada',
                    'visita' => 'Visita',
                    'whatsapp' => 'WhatsApp',
                    'correo' => 'Correo',
                    'tarea' => 'Tarea',
                ])
                ->default('nota')
                ->required(),
            Textarea::make('descripcion')
                ->label('Detalle')
                ->required()
                ->rows(3),
            DateTimePicker::make('vence_en')
                ->label('Recordar / vence')
                ->seconds(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('tipo')->badge(),
                TextColumn::make('descripcion')
                    ->label('Detalle')
                    ->wrap(),
                TextColumn::make('vence_en')
                    ->label('Vence')
                    ->dateTime('d/m/Y H:i'),
                IconColumn::make('hecha_en')
                    ->label('Hecha')
                    ->boolean()
                    ->getStateUsing(fn ($r) => (bool) $r->hecha_en),
                TextColumn::make('responsable.name')->label('Por'),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([
                Action::make('hecha')
                    ->label('Marcar hecha')
                    ->icon('heroicon-o-check')
                    ->visible(fn ($r) => ! $r->hecha_en)
                    ->action(fn ($r) => $r->update(['hecha_en' => now()])),
                DeleteAction::make(),
            ]);
    }
}
