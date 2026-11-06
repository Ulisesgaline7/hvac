<?php

namespace App\Services;

use App\Providers\RlsServiceProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/** Inventario y compras por proveedor para Finanzas. Solo lectura. */
class FinanzasInventario
{
    public function datos(Carbon $desde, Carbon $hasta): array
    {
        return RlsServiceProvider::comoInterno(fn () => $this->calcular($desde->copy()->startOfDay(), $hasta->copy()->endOfDay()));
    }

    private function calcular(Carbon $desde, Carbon $hasta): array
    {
        $activos = fn () => DB::table('insumos')->whereNull('deleted_at')->where('activo', true);

        $valor = $activos()->selectRaw('count(*) as n, coalesce(sum(stock * costo),0) as valor')->first();

        $bajo = $activos()->where('stock_minimo', '>', 0)->whereColumn('stock', '<=', 'stock_minimo')
            ->orderBy('nombre')->limit(15)->get(['codigo', 'nombre', 'stock', 'stock_minimo', 'unidad'])
            ->map(fn ($i) => (array) $i)->all();

        $mov = DB::table('movimientos_inventario')->whereBetween('created_at', [$desde, $hasta])
            ->selectRaw("tipo, count(*) as n, coalesce(sum(abs(cantidad) * coalesce(costo_unitario,0)),0) as valor")
            ->groupBy('tipo')->get()->keyBy('tipo');

        $porProveedor = DB::table('gastos')->leftJoin('proveedores', 'proveedores.id', '=', 'gastos.proveedor_id')
            ->whereBetween('gastos.fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->selectRaw('coalesce(proveedores.nombre, gastos.proveedor) as proveedor, count(*) as n, coalesce(sum(gastos.total),0) as total')
            ->groupByRaw('coalesce(proveedores.nombre, gastos.proveedor)')->orderByDesc('total')->limit(10)->get()
            ->map(fn ($p) => ['proveedor' => $p->proveedor, 'n' => (int) $p->n, 'total' => (float) $p->total])->all();

        return [
            'valor' => ['insumos' => (int) $valor->n, 'total' => round((float) $valor->valor, 2)],
            'bajo_minimo' => $bajo,
            'entradas' => (float) ($mov['entrada']->valor ?? 0),
            'consumo' => (float) ($mov['salida']->valor ?? 0),
            'por_proveedor' => $porProveedor,
        ];
    }
}
