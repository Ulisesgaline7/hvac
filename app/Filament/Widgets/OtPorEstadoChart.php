<?php

namespace App\Filament\Widgets;

use App\Enums\EstadoOt;
use App\Models\OrdenTrabajo;
use Filament\Widgets\ChartWidget;

class OtPorEstadoChart extends ChartWidget
{
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['administrador', 'coordinador', 'supervisor']) ?? false;
    }

    public function getHeading(): ?string
    {
        return 'Órdenes de trabajo por estado';
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $conteo = OrdenTrabajo::selectRaw('estado, count(*) as n')->groupBy('estado')->pluck('n', 'estado');
        $estados = EstadoOt::cases();

        return [
            'datasets' => [[
                'data' => array_map(fn ($e) => (int) ($conteo[$e->value] ?? 0), $estados),
                'backgroundColor' => ['#f59e0b', '#3b82f6', '#10b981', '#a78bfa', '#06b6d4', '#6366f1', '#22c55e', '#ef4444'],
            ]],
            'labels' => array_map(fn ($e) => $e->getLabel(), $estados),
        ];
    }
}
