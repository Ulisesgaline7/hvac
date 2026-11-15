<?php

namespace App\Services;

use App\Models\Contrato;
use App\Models\Factura;
use Carbon\Carbon;

class FacturacionContratos
{
    private const MESES = ['mensual' => 1, 'bimestral' => 2, 'trimestral' => 3, 'semestral' => 6, 'anual' => 12];

    /** Inicio del periodo de cobro vigente hoy, o null si el contrato aún no empieza o ya terminó. */
    public function periodoActual(Contrato $c, ?Carbon $hoy = null): ?Carbon
    {
        $hoy = ($hoy ?? now())->copy()->startOfDay();
        $inicio = Carbon::parse($c->vigencia_inicio)->startOfDay();
        $fin = Carbon::parse($c->vigencia_fin)->startOfDay();

        if ($hoy->lt($inicio) || $hoy->gt($fin)) {
            return null;
        }

        // Pago único: un solo periodo, el inicio de la vigencia (la factura es única por contrato y periodo).
        if (($c->periodicidad ?: config('cobros.contrato_periodicidad')) === 'unica') {
            return $inicio;
        }

        $paso = self::MESES[$c->periodicidad ?: config('cobros.contrato_periodicidad')] ?? 1;
        $k = intdiv((int) floor($inicio->diffInMonths($hoy, true)), $paso);

        return $inicio->copy()->addMonthsNoOverflow($k * $paso);
    }

    private function leyenda(Contrato $c, Carbon $periodo): string
    {
        $base = "Contrato #{$c->id} · periodo desde {$periodo->format('d/m/Y')}";
        try {
            $cot = \App\Models\Cotizacion::where('contrato_id', $c->id)->first();
            $ra = $cot ? ResumenAnual::resumen($cot) : null;
            if (! $ra) {
                $n = $c->equipos()->count();

                return $n ? "{$base} · {$n} equipos" : $base;
            }
            $per = $c->periodicidad ?: config('cobros.contrato_periodicidad');
            $plan = config("tarifario.descuentos.{$cot->descuento_contrato}.nombre", '');
            if ($per === 'unica') {
                $cuota = 'Pago único';
            } else {
                $paso = self::MESES[$per] ?? 1;
                $n = intdiv((int) floor(Carbon::parse($c->vigencia_inicio)->diffInMonths($periodo, true)), $paso) + 1;
                $cuota = "Cuota {$n} de " . (int) ceil(12 / $paso);
            }

            return "{$base} · {$cuota} · {$ra['texto']} · {$plan}";
        } catch (\Throwable $e) {
            return $base;
        }
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
            'notas' => $this->leyenda($c, $periodo),
        ]);
    }
}
