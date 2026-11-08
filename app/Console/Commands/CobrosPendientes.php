<?php

namespace App\Console\Commands;

use App\Models\Contrato;
use App\Models\Factura;
use App\Models\OrdenTrabajo;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CobrosPendientes extends Command
{
    protected $signature = 'cobros:pendientes';
    protected $description = 'Solo lectura: OT cerradas sin factura y periodos de contrato sin facturar';

    private const MESES = ['mensual' => 1, 'trimestral' => 3, 'semestral' => 6, 'anual' => 12];

    public function handle(): int
    {
        $this->line('== OT cerradas sin factura ==');
        $ots = OrdenTrabajo::where('estado', 'cerrada')
            ->whereIn('tipo', config('cobros.facturan_al_cerrar'))
            ->whereNotIn('id', Factura::whereNotNull('orden_trabajo_id')->select('orden_trabajo_id'))
            ->orderBy('id')->get();
        foreach ($ots as $ot) {
            $this->line("{$ot->numero}  cotización " . ($ot->cotizacion_id ?: '-') . '  precio_estimado ' . ($ot->precio_estimado ?? '-'));
        }
        $this->info('Total: ' . $ots->count());

        $this->line('');
        $this->line('== Periodos de contrato sin facturar ==');
        $hoy = now()->startOfDay();
        $n = 0;
        foreach (Contrato::where('estado', 'activo')->where('cuota_monto', '>', 0)->cursor() as $c) {
            $inicio = Carbon::parse($c->vigencia_inicio)->startOfDay();
            $fin = Carbon::parse($c->vigencia_fin)->startOfDay();
            $unico = ($c->periodicidad ?: config('cobros.contrato_periodicidad')) === 'unica';
            $paso = self::MESES[$c->periodicidad ?: config('cobros.contrato_periodicidad')] ?? 1;

            for ($k = 0; ($p = $inicio->copy()->addMonthsNoOverflow($k * $paso))->lte($hoy) && $p->lte($fin); $k++) {
                if (! Factura::where('contrato_id', $c->id)->whereDate('periodo', $p)->exists()) {
                    $this->line("Contrato {$c->id}  periodo {$p->format('d/m/Y')}");
                    $n++;
                }
                if ($unico) {
                    break;
                }
            }
        }
        $this->info("Total: $n (no se factura nada desde aquí)");

        return self::SUCCESS;
    }
}
