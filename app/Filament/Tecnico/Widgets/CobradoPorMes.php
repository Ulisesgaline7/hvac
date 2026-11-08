<?php

namespace App\Filament\Tecnico\Widgets;

use App\Support\TecnicoActual;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class CobradoPorMes extends ChartWidget
{
    protected static ?int $sort = 2;
    protected ?string $heading = 'Cobrado por mes (últimos 6 meses)';
    protected int|string|array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $t = TecnicoActual::id() ?? 0;
        $labels = [];
        $datos = [];

        for ($i = 5; $i >= 0; $i--) {
            $m = now()->startOfMonth()->subMonths($i);
            $labels[] = $m->translatedFormat('M Y');
            $datos[] = (float) DB::table('liquidaciones_tecnico')->where('tecnico_id', $t)
                ->where('estado', 'pagada')
                ->whereBetween('pagada_en', [$m, (clone $m)->endOfMonth()])
                ->sum('neto');
        }

        return [
            'datasets' => [['label' => 'Neto cobrado (L)', 'data' => $datos]],
            'labels' => $labels,
        ];
    }
}
