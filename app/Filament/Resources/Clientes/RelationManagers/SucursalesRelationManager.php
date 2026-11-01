<?php

namespace App\Filament\Resources\Clientes\RelationManagers;

use App\Filament\Resources\Sucursals\Schemas\SucursalForm;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SucursalesRelationManager extends RelationManager
{
    protected static string $relationship = 'sucursales';

    public function form(Schema $schema): Schema
    {
        return $schema->components(SucursalForm::campos());
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nombre')
            ->columns([
                TextColumn::make('nombre')->searchable(),
                TextColumn::make('direccion')
                    ->label('Dirección')
                    ->limit(40)
                    ->icon('heroicon-o-map-pin')
                    ->url(fn ($record): ?string => $record->urlMapa())
                    ->openUrlInNewTab(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
                AssociateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DissociateAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
