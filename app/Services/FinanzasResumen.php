<?php

namespace App\Services;

use App\Providers\RlsServiceProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/** Cifras financieras de solo lectura: facturado, cobrado, por cobrar, antigüedad y pagos a técnicos. */
class FinanzasResumen
{
    public function datos(Carbon $desde, Carbon $hasta): array
    {
        return RlsServiceProvider::comoInterno(fn () => $this->calcular($desde->copy()->startOfDay(), $hasta->copy()->endOfDay()));
    }

    private function calcular(Carbon $desde, Carbon $hasta): array
    {
        $rango = [$desde->toDateString(), $hasta->toDateString()];

        $facturado = DB::table('facturas')->where('estado', '!=', 'anulada')->whereBetween('fecha', $rango)
            ->selectRaw('count(*) as n, coalesce(sum(subtotal),0) as subtotal, coalesce(sum(isv),0) as isv, coalesce(sum(total),0) as total')
            ->first();

        $deContrato = (float) DB::table('facturas')->where('estado', '!=', 'anulada')->whereNotNull('contrato_id')
            ->whereBetween('fecha', $rango)->sum('total');

        $cobrado = DB::table('pagos')->whereBetween('fecha', $rango)
            ->selectRaw('tipo, coalesce(sum(monto),0) as monto')->groupBy('tipo')->pluck('monto', 'tipo');

        $anticiposSinFactura = (float) DB::table('pagos')->whereNull('factura_id')->sum('monto');

        // Por cobrar: todas las facturas con saldo, sin importar el rango.
        $hoy = now()->startOfDay();
        $abiertas = DB::table('facturas')->join('clientes', 'clientes.id', '=', 'facturas.cliente_id')
            ->whereNotIn('facturas.estado', ['pagada', 'anulada'])
            ->get(['facturas.numero', 'facturas.vence', 'facturas.total', 'facturas.pagado', 'clientes.razon_social'])
            ->map(fn ($f) => [
                'numero' => $f->numero,
                'cliente' => $f->razon_social,
                'saldo' => round((float) $f->total - (float) $f->pagado, 2),
                'dias' => Carbon::parse($f->vence)->startOfDay()->diffInDays($hoy, false),
            ])->filter(fn ($f) => $f['saldo'] > 0);

        $buckets = ['Por vencer' => 0.0, '1-30 días' => 0.0, '31-60 días' => 0.0, '61-90 días' => 0.0, 'Más de 90' => 0.0];
        foreach ($abiertas as $f) {
            $k = match (true) {
                $f['dias'] <= 0 => 'Por vencer',
                $f['dias'] <= 30 => '1-30 días',
                $f['dias'] <= 60 => '31-60 días',
                $f['dias'] <= 90 => '61-90 días',
                default => 'Más de 90',
            };
            $buckets[$k] = round($buckets[$k] + $f['saldo'], 2);
        }

        $porCliente = $abiertas->groupBy('cliente')->map(fn ($g) => round($g->sum('saldo'), 2))->sortDesc()->take(10);

        $tecnicos = DB::table('pagos_tecnico')->selectRaw('estado, count(*) as n, coalesce(sum(monto),0) as monto')
            ->groupBy('estado')->get()->keyBy('estado');

        return [
            'rango' => $rango,
            'facturado' => ['n' => (int) $facturado->n, 'subtotal' => (float) $facturado->subtotal, 'isv' => (float) $facturado->isv, 'total' => (float) $facturado->total, 'contrato' => $deContrato],
            'cobrado' => ['pagos' => (float) ($cobrado['pago'] ?? 0), 'anticipos' => (float) ($cobrado['anticipo'] ?? 0), 'total' => (float) $cobrado->sum()],
            'anticipos_sin_factura' => $anticiposSinFactura,
            'por_cobrar' => ['total' => round($abiertas->sum('saldo'), 2), 'facturas' => $abiertas->count(), 'antiguedad' => $buckets, 'top_clientes' => $porCliente->all()],
            'tecnicos' => $tecnicos->map(fn ($t) => ['n' => (int) $t->n, 'monto' => (float) $t->monto])->all(),
        ];
    }
}
