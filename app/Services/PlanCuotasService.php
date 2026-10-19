<?php

namespace App\Services;

use App\Models\Factura;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PlanCuotasService
{
    /** Divide el total de la factura (con ISV) en cuotas mensuales. La última absorbe el redondeo. */
    public function crear(Factura $f, int $cuotas, ?Carbon $primerVence = null): Collection
    {
        if (in_array($f->estado, ['pagada', 'anulada'], true)) {
            throw new DomainException('No se puede crear un plan de cuotas sobre una factura pagada o anulada.');
        }
        if ($cuotas < 2 || $cuotas > 12) {
            throw new DomainException('El plan debe tener de 2 a 12 cuotas.');
        }
        if ($f->cuotas()->exists()) {
            throw new DomainException('La factura ya tiene un plan de cuotas. Quítalo antes de crear otro.');
        }

        $total = (int) round((float) $f->total * 100);
        $base = intdiv($total, $cuotas);
        $primer = ($primerVence ?? ($f->vence->gt($f->fecha) ? $f->vence : $f->fecha->copy()->addMonthNoOverflow()))
            ->copy()->startOfDay();

        return DB::transaction(function () use ($f, $cuotas, $total, $base, $primer) {
            $creadas = collect();
            for ($i = 1; $i <= $cuotas; $i++) {
                $centavos = $i === $cuotas ? $total - $base * ($cuotas - 1) : $base;
                $creadas->push($f->cuotas()->create([
                    'cliente_id' => $f->cliente_id,
                    'numero' => $i,
                    'monto' => $centavos / 100,
                    'vence' => $primer->copy()->addMonthsNoOverflow($i - 1)->toDateString(),
                ]));
            }

            return $creadas;
        });
    }

    /** Estado de cada cuota: lo cobrado de la factura cubre las cuotas en orden. */
    public function estado(Factura $f): Collection
    {
        $restante = round((float) $f->pagado, 2);
        $hoy = now()->startOfDay();

        return $f->cuotas()->get()->map(function ($c) use (&$restante, $hoy) {
            $monto = (float) $c->monto;
            $aplicado = round(min($monto, max($restante, 0)), 2);
            $restante = round($restante - $aplicado, 2);
            $saldo = round($monto - $aplicado, 2);

            $estado = $saldo <= 0 ? 'pagada'
                : ($c->vence->lt($hoy) ? 'vencida' : ($aplicado > 0 ? 'parcial' : 'pendiente'));

            return ['cuota' => $c, 'aplicado' => $aplicado, 'saldo' => $saldo, 'estado' => $estado];
        });
    }

    public function quitar(Factura $f): int
    {
        return $f->cuotas()->delete();
    }
}
