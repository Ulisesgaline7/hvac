<?php

namespace App\Filament\Portal\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class EstadoGarantias extends ChartWidget
{
    protected static ?int $sort = 3;

    public function getHeading(): ?string
    {
        return 'Estado de mis garantías';
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $c = auth()->user()?->cliente_id;
        $hoy = now()->toDateString();
        $limite = now()->addDays(30)->toDateString();

        $r = $c ? DB::table('garantias')->join('ordenes_trabajo', 'ordenes_trabajo.id', '=', 'garantias.orden_trabajo_id')
            ->where('ordenes_trabajo.cliente_id', $c)
            ->selectRaw('count(*) filter (where garantias.hasta > ?) as vigentes, count(*) filter (where garantias.hasta between ? and ?) as por_vencer, count(*) filter (where garantias.hasta < ?) as vencidas', [$limite, $hoy, $limite, $hoy])
            ->first() : null;

        return [
            'datasets' => [['data' => [(int) ($r->vigentes ?? 0), (int) ($r->por_vencer ?? 0), (int) ($r->vencidas ?? 0)], 'backgroundColor' => ['#16a34a', '#d97706', '#9ca3af']]],
            'labels' => ['Vigentes', 'Vencen en 30 días', 'Vencidas'],
        ];
    }
}
