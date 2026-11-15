<?php

namespace App\Filament\Portal\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class VisitasPorMes extends ChartWidget
{
    protected static ?int $sort = 1;

    public function getHeading(): ?string
    {
        return 'Servicios por mes (últimos 6 meses)';
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $c = auth()->user()?->cliente_id;
        $meses = collect(range(5, 0))->map(fn ($i) => now()->startOfMonth()->subMonths($i));
        $conteo = $c ? DB::table('ordenes_trabajo')->where('cliente_id', $c)
            ->whereRaw("coalesce(fecha_programada::date, created_at::date) >= ?", [$meses->first()->toDateString()])
            ->selectRaw("to_char(coalesce(fecha_programada::date, created_at::date), 'YYYY-MM') as m, count(*) as n")
            ->groupBy('m')->pluck('n', 'm') : collect();

        return [
            'datasets' => [['label' => 'Órdenes de trabajo', 'data' => $meses->map(fn (Carbon $m) => (int) ($conteo[$m->format('Y-m')] ?? 0))->all()]],
            'labels' => $meses->map(fn (Carbon $m) => ucfirst($m->locale('es')->translatedFormat('M Y')))->all(),
        ];
    }
}
