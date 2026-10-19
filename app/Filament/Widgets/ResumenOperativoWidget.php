<?php

namespace App\Filament\Widgets;

use App\Enums\EstadoOt;
use App\Models\OrdenTrabajo;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ResumenOperativoWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Operación';

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['administrador', 'coordinador']) ?? false;
    }

    protected function getStats(): array
    {
        $abiertas = [EstadoOt::Cerrada->value, EstadoOt::Cancelada->value];

        return [
            Stat::make('Sin técnico asignado', OrdenTrabajo::where('estado', EstadoOt::Pendiente->value)->count())
                ->description('Pendientes de asignar')->color('warning'),
            Stat::make('En revisión', OrdenTrabajo::where('estado', EstadoOt::EnRevision->value)->count())
                ->description('Esperan cierre')->color('info'),
            Stat::make('En proceso', OrdenTrabajo::where('estado', EstadoOt::EnProceso->value)->count()),
            Stat::make('Programadas hoy', OrdenTrabajo::whereDate('fecha_programada', today())->whereNotIn('estado', $abiertas)->count()),
            Stat::make('Vencidas', OrdenTrabajo::whereDate('fecha_programada', '<', today())->whereNotIn('estado', $abiertas)->count())
                ->description('Fecha programada pasada')->color('danger'),
        ];
    }
}
