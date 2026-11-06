<?php

namespace App\Services;

use App\Providers\RlsServiceProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/** Gastos, impuestos (ISV), cobros detallados y pagos a técnicos. Solo lectura. */
class FinanzasGastos
{
    public const CATEGORIAS = [
        'insumos' => 'Insumos y materiales',
        'gas' => 'Refrigerante / gas',
        'repuestos' => 'Repuestos',
        'herramientas' => 'Herramientas y equipo',
        'transporte' => 'Transporte y combustible',
        'viaticos' => 'Viáticos',
        'otros' => 'Otros',
    ];

    public function datos(Carbon $desde, Carbon $hasta): array
    {
        return RlsServiceProvider::comoInterno(fn () => $this->calcular($desde->copy()->startOfDay(), $hasta->copy()->endOfDay()));
    }

    private function calcular(Carbon $desde, Carbon $hasta): array
    {
        $rango = [$desde->toDateString(), $hasta->toDateString()];
        $hoy = now()->startOfDay();

        $porCategoria = DB::table('gastos')->whereBetween('fecha', $rango)
            ->selectRaw('categoria, count(*) as n, coalesce(sum(subtotal),0) as subtotal, coalesce(sum(isv),0) as isv, coalesce(sum(total),0) as total')
            ->groupBy('categoria')->get()
            ->map(fn ($g) => ['categoria' => self::CATEGORIAS[$g->categoria] ?? $g->categoria, 'n' => (int) $g->n, 'subtotal' => (float) $g->subtotal, 'isv' => (float) $g->isv, 'total' => (float) $g->total])
            ->all();

        $gastosTotal = [
            'subtotal' => array_sum(array_column($porCategoria, 'subtotal')),
            'isv' => array_sum(array_column($porCategoria, 'isv')),
            'total' => array_sum(array_column($porCategoria, 'total')),
        ];

        $lista = DB::table('gastos')->whereBetween('fecha', $rango)->orderByDesc('fecha')->orderByDesc('id')->limit(30)
            ->get(['id', 'fecha', 'proveedor', 'concepto', 'categoria', 'factura_proveedor', 'isv', 'total'])
            ->map(fn ($g) => (array) $g + ['cat' => self::CATEGORIAS[$g->categoria] ?? $g->categoria])->all();

        $isvVentas = (float) DB::table('facturas')->where('estado', '!=', 'anulada')->whereBetween('fecha', $rango)->sum('isv');
        $isvCobrado = (float) DB::table('facturas')->where('estado', '!=', 'anulada')->whereBetween('fecha', $rango)
            ->whereRaw('total > 0')->selectRaw('coalesce(sum(isv * pagado / total),0) as v')->value('v');

        $porMetodo = DB::table('pagos')->whereBetween('fecha', $rango)
            ->selectRaw("coalesce(metodo,'sin método') as metodo, coalesce(sum(monto),0) as monto")->groupBy('metodo')->pluck('monto', 'metodo')
            ->map(fn ($v) => (float) $v)->all();

        $ultimosCobros = DB::table('pagos')->leftJoin('clientes', 'clientes.id', '=', 'pagos.cliente_id')
            ->whereBetween('pagos.fecha', $rango)->orderByDesc('pagos.fecha')->orderByDesc('pagos.id')->limit(15)
            ->get(['pagos.numero', 'pagos.fecha', 'pagos.tipo', 'pagos.monto', 'pagos.metodo', 'clientes.razon_social'])
            ->map(fn ($p) => (array) $p)->all();

        $abiertas = DB::table('facturas')->leftJoin('clientes', 'clientes.id', '=', 'facturas.cliente_id')
            ->whereNotIn('facturas.estado', ['pagada', 'anulada'])
            ->get(['facturas.numero', 'facturas.vence', 'facturas.total', 'facturas.pagado', 'clientes.razon_social'])
            ->map(fn ($f) => [
                'numero' => $f->numero,
                'cliente' => $f->razon_social,
                'vence' => Carbon::parse($f->vence)->format('d/m/Y'),
                'saldo' => round((float) $f->total - (float) $f->pagado, 2),
                'atraso' => max(0, (int) Carbon::parse($f->vence)->startOfDay()->diffInDays($hoy, false)),
            ])->filter(fn ($f) => $f['saldo'] > 0)->sortByDesc('atraso')->take(15)->values()->all();

        $tecnicosPagos = DB::table('pagos_tecnico')->whereBetween('created_at', [$desde, $hasta])
            ->selectRaw('estado, count(*) as n, coalesce(sum(monto),0) as monto')->groupBy('estado')->get()
            ->map(fn ($t) => ['estado' => $t->estado, 'n' => (int) $t->n, 'monto' => (float) $t->monto])->all();

        $liq = DB::table('liquidaciones_tecnico')->whereBetween('semana_fin', $rango)
            ->selectRaw('estado, count(*) as n, coalesce(sum(bruto),0) as bruto, coalesce(sum(retencion),0) as retencion, coalesce(sum(neto),0) as neto')
            ->groupBy('estado')->get()
            ->map(fn ($t) => ['estado' => $t->estado, 'n' => (int) $t->n, 'bruto' => (float) $t->bruto, 'retencion' => (float) $t->retencion, 'neto' => (float) $t->neto])->all();

        return [
            'gastos' => ['por_categoria' => $porCategoria, 'total' => $gastosTotal, 'lista' => $lista],
            'isv' => ['ventas' => $isvVentas, 'ventas_cobrado' => round($isvCobrado, 2), 'compras' => $gastosTotal['isv'], 'neto' => round($isvVentas - $gastosTotal['isv'], 2)],
            'cobros' => ['por_metodo' => $porMetodo, 'ultimos' => $ultimosCobros, 'abiertas' => $abiertas],
            'tecnicos' => ['pagos' => $tecnicosPagos, 'liquidaciones' => $liq, 'total_pagos' => array_sum(array_column($tecnicosPagos, 'monto'))],
        ];
    }
}
