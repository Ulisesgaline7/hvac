<?php

namespace App\Filament\Resources\Tecnicos\RelationManagers;

use Carbon\Carbon;
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
                'certificacion_refrigerantes' => 'Certificación de refrigerantes',
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
                TextColumn::make('vencimiento')
                    ->date()
                    ->sortable()
                    ->color(function ($state) {
                        if (! $state) {
                            return null;
                        }
                        $fecha = Carbon::parse($state);

                        return $fecha->isPast() ? 'danger' : ($fecha->lt(now()->addDays(30)) ? 'warning' : 'success');
                    }),
                TextColumn::make('notas')->limit(40),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
