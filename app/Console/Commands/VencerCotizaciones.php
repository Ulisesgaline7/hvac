<?php

namespace App\Console\Commands;

use App\Models\Cotizacion;
use App\Services\CotizacionFlujo;
use Illuminate\Console\Command;

class VencerCotizaciones extends Command
{
    protected $signature = 'cotizaciones:vencer {--simulacro : Solo lista las cotizaciones, no cambia nada}';
    protected $description = 'Marca como vencidas las cotizaciones enviadas fuera de vigencia y factura la visita técnica de origen';

    public function handle(CotizacionFlujo $flujo): int
    {
        $vencidas = Cotizacion::where('estado', 'enviada')
            ->whereNotNull('valida_hasta')
            ->whereDate('valida_hasta', '<', now()->toDateString())
            ->get();

        if ($this->option('simulacro')) {
            foreach ($vencidas as $c) {
                $this->line("{$c->numero} · válida hasta {$c->valida_hasta->format('d/m/Y')} · visita OT " . ($c->visita_ot_id ?: '-'));
            }
            $this->info('Cotizaciones que se vencerían: ' . $vencidas->count());

            return self::SUCCESS;
        }

        $ok = 0;
        foreach ($vencidas as $c) {
            try {
                $flujo->vencer($c);
                $ok++;
            } catch (\Throwable $e) {
                $this->error("{$c->numero}: {$e->getMessage()}");
            }
        }
        $this->info("Cotizaciones vencidas: {$ok} de {$vencidas->count()}");

        return self::SUCCESS;
    }
}
