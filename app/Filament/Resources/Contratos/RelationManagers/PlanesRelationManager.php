<?php

namespace App\Filament\Resources\Contratos\RelationManagers;

use App\Enums\EstadoContrato;
use App\Enums\Frecuencia;
use App\Services\GeneradorCalendario;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlanesRelationManager extends RelationManager
{
    protected static string $relationship = 'planes';

    protected static ?string $title = 'Planes por tipo de equipo';

    private function recalcular(): void
    {
        $contrato = $this->getOwnerRecord()->fresh();

        if ($contrato->estado === EstadoContrato::Activo) {
            app(GeneradorCalendario::class)->recalcular($contrato);
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('tipo_equipo_id')
                ->label('Tipo de equipo')
                ->relationship('tipoEquipo', 'nombre')
                ->required(),
            Select::make('frecuencia')->options(Frecuencia::class)->required(),
            TextInput::make('anticipacion_dias')
                ->label('Anticipación (días)')
                ->numeric()
                ->minValue(0)
                ->helperText('Vacío = la del contrato.'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('tipoEquipo.nombre')->label('Tipo de equipo'),
                TextColumn::make('frecuencia')->badge(),
                TextColumn::make('anticipacion_dias')->label('Anticipación'),
            ])
            ->headerActions([CreateAction::make()->after(fn () => $this->recalcular())])
            ->recordActions([
                EditAction::make()->after(fn () => $this->recalcular()),
                DeleteAction::make()->after(fn () => $this->recalcular()),
            ]);
    }
}
