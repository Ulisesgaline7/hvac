<?php

namespace App\Filament\Tecnico\Widgets;

use App\Models\OrdenTrabajo;
use App\Support\TecnicoActual;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ProximasOrdenes extends TableWidget
{
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Próximas órdenes')
            ->query(
                OrdenTrabajo::query()
                    ->whereIn('id', TecnicoActual::otIds())
                    ->whereIn('estado', ['pendiente', 'asignada', 'en_proceso', 'en_espera'])
                    ->orderByRaw('fecha_programada is null, fecha_programada asc')
            )
            ->columns([
                TextColumn::make('numero')->label('OT'),
                TextColumn::make('tipo')->label('Tipo')->badge(),
                TextColumn::make('estado')->label('Estado')->badge(),
                TextColumn::make('fecha_programada')->label('Fecha')->date('d/m/Y'),
                TextColumn::make('hora_programada')->label('Hora'),
            ])
            ->paginated([5])
            ->emptyStateHeading('No tienes órdenes pendientes');
    }
}
