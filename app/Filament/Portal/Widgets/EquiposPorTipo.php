<?php

namespace App\Filament\Portal\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class EquiposPorTipo extends ChartWidget
{
    protected static ?int $sort = 2;

    public function getHeading(): ?string
    {
        return 'Mis equipos por tipo';
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $c = auth()->user()?->cliente_id;
        $filas = $c ? DB::table('equipos')->join('areas', 'areas.id', '=', 'equipos.area_id')
            ->join('sucursales', 'sucursales.id', '=', 'areas.sucursal_id')
            ->leftJoin('tipos_equipo', 'tipos_equipo.id', '=', 'equipos.tipo_equipo_id')
            ->where('sucursales.cliente_id', $c)->whereNull('equipos.deleted_at')
            ->selectRaw("coalesce(tipos_equipo.nombre, 'Sin tipo') as t, count(*) as n")
            ->groupBy('t')->orderByDesc('n')->get() : collect();

        return [
            'datasets' => [['data' => $filas->pluck('n')->map(fn ($n) => (int) $n)->all(), 'backgroundColor' => ['#2563eb', '#16a34a', '#d97706', '#dc2626', '#7c3aed', '#0891b2', '#db2777', '#65a30d']]],
            'labels' => $filas->pluck('t')->all(),
        ];
    }
}
