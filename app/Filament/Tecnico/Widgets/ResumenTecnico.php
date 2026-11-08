<?php

namespace App\Filament\Tecnico\Widgets;

use App\Support\TecnicoActual;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class ResumenTecnico extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $t = TecnicoActual::id() ?? 0;
        $ini = now()->startOfMonth();

        $ots = fn () => DB::table('ordenes_trabajo')->whereNull('deleted_at')
            ->whereIn('id', TecnicoActual::otIds());

        $activas = $ots()->whereIn('estado', ['asignada', 'en_proceso', 'en_espera'])->count();

        $cerradasMes = $ots()->where('estado', 'cerrada')->where('cerrada_en', '>=', $ini)->count();

        $cerradas = $ots()->where('estado', 'cerrada')->whereNotNull('tardia');
        $total = (clone $cerradas)->count();
        $aTiempo = (clone $cerradas)->where('tardia', false)->count();
        $puntual = $total > 0 ? round($aTiempo * 100 / $total) . ' %' : 'Sin datos';

        $cobrado = (float) DB::table('liquidaciones_tecnico')->where('tecnico_id', $t)
            ->where('estado', 'pagada')->where('pagada_en', '>=', $ini)->sum('neto');

        $porCobrar = (float) DB::table('pagos_tecnico')->where('tecnico_id', $t)
            ->whereNotIn('estado', ['sin_tarifa', 'retorno', 'liquidado'])->sum('monto')
            + (float) DB::table('liquidaciones_tecnico')->where('tecnico_id', $t)
                ->where('estado', '!=', 'pagada')->sum('neto');

        $retornos = DB::table('pagos_tecnico')->where('tecnico_id', $t)
            ->where('estado', 'retorno')->where('created_at', '>=', $ini)->count();

        return [
            Stat::make('OTs activas', $activas),
            Stat::make('Cerradas este mes', $cerradasMes),
            Stat::make('Puntualidad', $puntual)->description('OTs cerradas a tiempo'),
            Stat::make('Cobrado este mes', 'L ' . number_format($cobrado, 2)),
            Stat::make('Por cobrar', 'L ' . number_format($porCobrar, 2)),
            Stat::make('Retornos del mes', $retornos)
                ->description('Retrabajos por falla de ejecución')
                ->color($retornos > 0 ? 'danger' : 'success'),
        ];
    }
}
