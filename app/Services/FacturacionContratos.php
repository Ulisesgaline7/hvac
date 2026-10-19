<?php

namespace App\Services;

use App\Models\Contrato;
use App\Models\Factura;
use Carbon\Carbon;

class FacturacionContratos
{
    private const MESES = ['mensual' => 1, 'trimestral' => 3, 'semestral' => 6, 'anual' => 12];

    /** Inicio del periodo de cobro vigente hoy, o null si el contrato aún no empieza o ya terminó. */
    public function periodoActual(Contrato $c, ?Carbon $hoy = null): ?Carbon
    {
        $hoy = ($hoy ?? now())->copy()->startOfDay();
        $inicio = Carbon::parse($c->vigencia_inicio)->startOfDay();
        $fin = Carbon::parse($c->vigencia_fin)->startOfDay();

        if ($hoy->lt($inicio) || $hoy->gt($fin)) {
            return null;
        }

        $paso = self::MESES[$c->periodicidad ?: config('cobros.contrato_periodicidad')] ?? 1;
        $k = intdiv((int) floor($inicio->diffInMonths($hoy, true)), $paso);

        return $inicio->copy()->addMonthsNoOverflow($k * $paso);
    }

    /** Genera la factura del periodo vigente. Idempotente por (contrato, periodo). */
    public function facturar(Contrato $c, ?Carbon $hoy = null): ?Factura
    {
        if ((float) $c->cuota_monto <= 0) {
            return null;
        }

        $periodo = $this->periodoActual($c, $hoy);
        if (! $periodo) {
            return null;
        }

        if ($f = Factura::where('contrato_id', $c->id)->whereDate('periodo', $periodo)->first()) {
            return $f;
        }

        $subtotal = round((float) $c->cuota_monto, 2);
        $isv = round($subtotal * config('tarifario.isv'), 2);
        $fecha = ($hoy ?? now())->copy()->startOfDay();

        return Factura::create([
            'cliente_id' => $c->cliente_id,
            'contrato_id' => $c->id,
            'periodo' => $periodo->toDateString(),
            'fecha' => $fecha->toDateString(),
            'vence' => $fecha->copy()->addDays((int) config('cobros.dias_credito'))->toDateString(),
            'subtotal' => $subtotal,
            'isv' => $isv,
            'total' => round($subtotal + $isv, 2),
            'notas' => "Contrato #{$c->id} · periodo desde {$periodo->format('d/m/Y')}",
        ]);
    }
}
