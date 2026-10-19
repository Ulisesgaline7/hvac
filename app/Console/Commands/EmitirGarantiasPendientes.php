<?php

namespace App\Console\Commands;

use App\Models\OrdenTrabajo;
use App\Providers\RlsServiceProvider;
use App\Services\GarantiaService;
use Illuminate\Console\Command;

class EmitirGarantiasPendientes extends Command
{
    protected $signature = 'garantias:emitir-pendientes';
    protected $description = 'Emite garantías de las OT cerradas que aún no tienen';

    public function handle(GarantiaService $svc): int
    {
        $total = RlsServiceProvider::comoInterno(function () use ($svc) {
            $n = 0;
            OrdenTrabajo::where('estado', 'cerrada')
                ->whereHas('lineas', fn ($q) => $q->whereNotIn('id', \App\Models\Garantia::whereNotNull('orden_trabajo_equipo_id')->select('orden_trabajo_equipo_id')))
                ->each(function ($ot) use ($svc, &$n) {
                    $n += $svc->emitir($ot)->count();
                });

            return $n;
        });

        $this->info("Garantías revisadas/emitidas: {$total}");

        return self::SUCCESS;
    }
}
