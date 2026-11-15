<?php

namespace App\Filament\Resources\Colaboradores\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocumentosRelationManager extends RelationManager
{
    protected static string $relationship = 'documentos';

    protected static ?string $title = 'Documentos';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('tipo')->options([
                'identificacion' => 'Identificación',
                'contrato' => 'Contrato',
                'antecedentes' => 'Antecedentes',
                'seguro' => 'Seguro',
                'otro' => 'Otro',
            ])->required(),
            DatePicker::make('vencimiento'),
            Textarea::make('notas')->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('tipo')
            ->columns([
                TextColumn::make('tipo')->badge(),
                TextColumn::make('vencimiento')->date()->sortable(),
                TextColumn::make('notas')->limit(40),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
