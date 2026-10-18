<?php

namespace App\Filament\Resources\Contratos\RelationManagers;

use App\Enums\EstadoContrato;
use App\Enums\Frecuencia;
use App\Services\GeneradorCalendario;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class LineasRelationManager extends RelationManager
{
    protected static string $relationship = 'lineas';

    protected static ?string $title = 'Equipos cubiertos';

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
            Select::make('equipo_id')
                ->label('Equipo')
                ->relationship('equipo', 'codigo', fn ($query) => $query->whereIn(
                    'area_id',
                    DB::table('areas')
                        ->join('sucursales', 'sucursales.id', '=', 'areas.sucursal_id')
                        ->where('sucursales.cliente_id', $this->getOwnerRecord()->cliente_id)
                        ->select('areas.id')
                ))
                ->searchable()
                ->preload()
                ->required(),
            Select::make('frecuencia')
                ->options(Frecuencia::class)
                ->helperText('Vacío = usa la frecuencia del plan por tipo de equipo.'),
            DatePicker::make('fecha_inicio')
                ->required()
                ->default(fn () => $this->getOwnerRecord()->vigencia_inicio),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('equipo.codigo')->label('Equipo')->searchable(),
                TextColumn::make('equipo.tipo.nombre')->label('Tipo'),
                TextColumn::make('frecuencia')->badge(),
                TextColumn::make('fecha_inicio')->date(),
                TextColumn::make('proxima_fecha')->label('Próximo servicio')->date(),
            ])
            ->headerActions([CreateAction::make()->after(fn () => $this->recalcular())])
            ->recordActions([
                EditAction::make()->after(fn () => $this->recalcular()),
                DeleteAction::make()->after(fn () => $this->recalcular()),
            ]);
    }
}
