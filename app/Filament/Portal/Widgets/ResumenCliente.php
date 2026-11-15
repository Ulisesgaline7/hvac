<?php

namespace App\Filament\Portal\Widgets;

use App\Models\Contrato;
use App\Models\Cotizacion;
use App\Models\Equipo;
use App\Models\Garantia;
use App\Models\OrdenTrabajo;
use App\Models\SolicitudCliente;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class ResumenCliente extends StatsOverviewWidget
{
    protected static ?int $sort = -1;

    protected function getStats(): array
    {
        $c = auth()->user()?->cliente_id;

        if (! $c) {
            return [];
        }

        $porAprobar = Cotizacion::where('cliente_id', $c)->where('estado', 'enviada')->count();
        $contratos = Contrato::where('cliente_id', $c)->where('estado', 'activo')->count();
        $otAbiertas = OrdenTrabajo::where('cliente_id', $c)->whereNull('cerrada_en')->count();
        $garantias = Garantia::whereHas('ordenTrabajo', fn ($o) => $o->where('cliente_id', $c))
            ->whereDate('hasta', '>=', now())->count();
        $equipos = Equipo::whereIn('area_id', fn ($s) => $s->select('areas.id')->from('areas')
            ->join('sucursales', 'sucursales.id', '=', 'areas.sucursal_id')
            ->where('sucursales.cliente_id', $c))->count();
        $abiertas = SolicitudCliente::where('cliente_id', $c)->whereIn('estado', ['nueva', 'en_revision'])->count();
        $saldo = (float) DB::table('facturas')->where('cliente_id', $c)->selectRaw('coalesce(sum(total - pagado), 0) as s')->value('s');

        return [
            Stat::make('Cotizaciones por revisar', $porAprobar),
            Stat::make('Contratos activos', $contratos),
            Stat::make('Órdenes en curso', $otAbiertas),
            Stat::make('Garantías vigentes', $garantias),
            Stat::make('Equipos registrados', $equipos),
            Stat::make('Solicitudes abiertas', $abiertas),
            Stat::make('Saldo pendiente', 'L ' . number_format($saldo, 2)),
        ];
    }
}
