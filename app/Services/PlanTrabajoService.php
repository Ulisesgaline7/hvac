<?php

namespace App\Services;

use App\Models\OrdenTrabajo;
use App\Models\Tecnico;
use DomainException;
use Illuminate\Support\Facades\DB;

/** Plan de trabajo de una OT: alcance, minutos por equipo y reparto de equipos entre la cuadrilla. */
class PlanTrabajoService
{
    private const MIN_DIA = 480;
    private const FACTOR_LIDER_GRANDE = 0.5;

    /** BTU/h → equipos por técnico por día (sección 8 del tarifario). */
    private const PRODUCTIVIDAD = [12000 => 7, 18000 => 6, 24000 => 5, 36000 => 4, 48000 => 3.5, 60000 => 3];

    /** Minutos de un equipo según su capacidad. Null si no hay capacidad o no hay productividad definida. */
    public function minutosPara(?float $valor, ?string $unidad): ?int
    {
        if (! $valor || ! $unidad) {
            return null;
        }
        $btu = match (strtoupper(trim($unidad))) {
            'BTU/H', 'BTU' => $valor,
            'TR' => $valor * 12000,
            default => null,
        };
        if ($btu === null) {
            return null;
        }

        foreach (self::PRODUCTIVIDAD as $tope => $porDia) {
            if ($btu <= $tope) {
                return (int) round(self::MIN_DIA / $porDia);
            }
        }

        return null;
    }

    /** Rellena minutos y trabajo previsto de las líneas que aún no los tienen. Devuelve cuántas quedaron sin minutos. */
    public function generar(OrdenTrabajo $ot): int
    {
        $sinMinutos = 0;
        $alcance = $ot->alcance ?: $ot->descripcion;

        foreach ($ot->lineas()->with('equipo')->get() as $l) {
            $min = $l->minutos_estimados ?? $this->minutosPara(
                $l->equipo?->capacidad_valor !== null ? (float) $l->equipo->capacidad_valor : null,
                $l->equipo?->capacidad_unidad
            );
            if ($min === null) {
                $sinMinutos++;
            }
            $l->update([
                'minutos_estimados' => $min,
                'trabajo_previsto' => $l->trabajo_previsto ?: $alcance,
            ]);
        }

        return $sinMinutos;
    }

    /** @return array{lineas: array<int,int>, carga: array<int,int>} línea_id => tecnico_id, y minutos por técnico */
    public function calcularReparto(OrdenTrabajo $ot): array
    {
        $cuadrilla = DB::table('orden_trabajo_tecnico')->where('orden_trabajo_id', $ot->id)->get()
            ->filter(fn ($f) => $f->rol !== 'ayudante')->values();
        if ($cuadrilla->isEmpty()) {
            throw new DomainException('Asigne la cuadrilla antes de repartir los equipos.');
        }

        $grande = $cuadrilla->count() >= 4;
        $peso = [];
        foreach ($cuadrilla as $f) {
            $peso[(int) $f->tecnico_id] = ($f->rol === 'lider' && $grande) ? self::FACTOR_LIDER_GRANDE : 1.0;
        }

        $carga = array_fill_keys(array_keys($peso), 0);
        $asignado = [];
        $lineas = $ot->lineas()->get()->sortByDesc(fn ($l) => (int) $l->minutos_estimados);

        foreach ($lineas as $l) {
            // El siguiente equipo va a quien lleva menos carga relativa a su capacidad.
            $elegido = collect($carga)->map(fn ($m, $id) => $m / $peso[$id])->sort()->keys()->first();
            $asignado[$l->id] = (int) $elegido;
            $carga[$elegido] += (int) $l->minutos_estimados;
        }

        return ['lineas' => $asignado, 'carga' => $carga];
    }

    public function repartir(OrdenTrabajo $ot): array
    {
        $r = $this->calcularReparto($ot);
        DB::transaction(function () use ($ot, $r) {
            foreach ($r['lineas'] as $lineaId => $tecnicoId) {
                $ot->lineas()->whereKey($lineaId)->update(['tecnico_id' => $tecnicoId]);
            }
        });

        return $r;
    }
}
