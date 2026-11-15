<?php

namespace App\Filament\Widgets;

use App\Models\OrdenTrabajo;
use Filament\Widgets\ChartWidget;

class OtPorMesChart extends ChartWidget
{
    protected static ?int $sort = 3;

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['administrador', 'coordinador', 'supervisor']) ?? false;
    }

    public function getHeading(): ?string
    {
        return 'Órdenes de trabajo por mes (últimos 6)';
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $meses = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i)->startOfMonth());

        return [
            'datasets' => [[
                'label' => 'Creadas',
                'data' => $meses->map(fn ($m) => OrdenTrabajo::whereBetween('created_at', [$m, $m->copy()->endOfMonth()])->count())->all(),
                'backgroundColor' => '#3b82f6',
            ]],
            'labels' => $meses->map(fn ($m) => $m->translatedFormat('M Y'))->all(),
        ];
    }
}
