<?php

namespace App\Services;

use App\Enums\EstadoContrato;
use App\Enums\EstadoOt;
use App\Enums\Frecuencia;
use App\Enums\TipoOt;
use App\Models\Contrato;
use App\Models\OrdenTrabajo;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GeneradorCalendario
{
    /** Fechas de servicio de toda la vigencia para una frecuencia. */
    public function fechas(CarbonInterface $inicio, CarbonInterface $fin, Frecuencia $frecuencia): array
    {
        $fechas = [];
        $inicio = $inicio->copy()->startOfDay();
        $fin = $fin->copy()->startOfDay();

        for ($i = 0; ($fecha = $inicio->copy()->addMonthsNoOverflow($i * $frecuencia->meses()))->lte($fin); $i++) {
            $fechas[] = $fecha;
        }

        return $fechas;
    }

    /** Crea las OT que caen dentro de la ventana de anticipación. Idempotente. Devuelve cuántas creó. */
    public function generar(Contrato $contrato, ?CarbonInterface $hoy = null): int
    {
        $hoy = ($hoy ?? now())->copy()->startOfDay();

        if (! $this->aplica($contrato)) {
            return 0;
        }

        $existentes = $this->existentes($contrato);
        $pendientes = []; // sucursal_id => fecha => [equipo_id]

        foreach ($this->lineas($contrato) as $l) {
            $limite = $hoy->copy()->addDays($l['anticipacion']);
            $fechas = $this->fechas($l['inicio'], $contrato->vigencia_fin, $l['frecuencia']);

            foreach ($fechas as $fecha) {
                if ($fecha->lte($limite) && ! isset($existentes[$l['equipo_id'] . '|' . $fecha->toDateString()])) {
                    $pendientes[$l['sucursal_id']][$fecha->toDateString()][] = $l['equipo_id'];
                }
            }

            $proxima = collect($fechas)->first(fn ($f) => $f->gte($hoy));
            $l['linea']->updateQuietly(['proxima_fecha' => $proxima?->toDateString()]);
        }

        $creadas = 0;

        DB::transaction(function () use ($contrato, $pendientes, &$creadas) {
            ksort($pendientes);

            foreach ($pendientes as $sucursalId => $porFecha) {
                ksort($porFecha);

                foreach ($porFecha as $fecha => $equipoIds) {
                    $ot = OrdenTrabajo::create([
                        'tipo' => TipoOt::Preventivo,
                        'prioridad' => 'normal',
                        'estado' => EstadoOt::Pendiente,
                        'cliente_id' => $contrato->cliente_id,
                        'sucursal_id' => $sucursalId,
                        'contrato_id' => $contrato->id,
                        'descripcion' => 'Mantenimiento preventivo programado',
                        'fecha_programada' => $fecha,
                        'fecha_original' => $fecha,
                    ]);
                    $ot->equipos()->attach(array_values(array_unique($equipoIds)));
                    $creadas++;
                }
            }
        });

        return $creadas;
    }

    /**
     * PRE-04: quita de las OT pendientes futuras lo que ya no corresponde al contrato
     * (equipo retirado, frecuencia cambiada, contrato cancelado) y completa lo que falte.
     * No toca OT iniciadas, completadas ni cerradas.
     */
    public function recalcular(Contrato $contrato, ?CarbonInterface $hoy = null): array
    {
        $hoy = ($hoy ?? now())->copy()->startOfDay();
        $esperadas = $this->esperadas($contrato);
        $canceladas = 0;

        DB::transaction(function () use ($contrato, $hoy, $esperadas, &$canceladas) {
            $ots = OrdenTrabajo::where('contrato_id', $contrato->id)
                ->where('tipo', TipoOt::Preventivo->value)
                ->where('estado', EstadoOt::Pendiente->value)
                ->whereDate('fecha_programada', '>=', $hoy->toDateString())
                ->get();

            foreach ($ots as $ot) {
                $fecha = ($ot->fecha_original ?? $ot->fecha_programada)?->toDateString();
                if (! $fecha) {
                    continue;
                }

                $filas = DB::table('orden_trabajo_equipo')->where('orden_trabajo_id', $ot->id)->get(['id', 'equipo_id']);
                $sobran = $filas->filter(fn ($f) => ! isset($esperadas[$f->equipo_id . '|' . $fecha]))->pluck('id');

                if ($sobran->isEmpty()) {
                    continue;
                }

                DB::table('orden_trabajo_equipo')->whereIn('id', $sobran)->delete();

                if ($sobran->count() === $filas->count()) {
                    $ot->update(['estado' => EstadoOt::Cancelada]);
                    $canceladas++;
                }
            }
        });

        return ['canceladas' => $canceladas, 'creadas' => $this->generar($contrato, $hoy)];
    }

    private function aplica(Contrato $contrato): bool
    {
        return $contrato->estado === EstadoContrato::Activo && $contrato->tipo->generaCalendario();
    }

    /** Conjunto "equipo|fecha" de todo lo que el contrato debe cubrir en su vigencia. */
    private function esperadas(Contrato $contrato): array
    {
        if (! $this->aplica($contrato)) {
            return [];
        }

        $set = [];
        foreach ($this->lineas($contrato) as $l) {
            foreach ($this->fechas($l['inicio'], $contrato->vigencia_fin, $l['frecuencia']) as $fecha) {
                $set[$l['equipo_id'] . '|' . $fecha->toDateString()] = true;
            }
        }

        return $set;
    }

    /** Pares equipo|fecha ya cubiertos por alguna OT del contrato (incluye canceladas a mano, para no resucitarlas). */
    private function existentes(Contrato $contrato): array
    {
        return DB::table('orden_trabajo_equipo as ote')
            ->join('ordenes_trabajo as ot', 'ot.id', '=', 'ote.orden_trabajo_id')
            ->where('ot.contrato_id', $contrato->id)
            ->where('ot.tipo', TipoOt::Preventivo->value)
            ->whereNull('ot.deleted_at')
            ->get(['ote.equipo_id', 'ot.fecha_original'])
            ->mapWithKeys(fn ($r) => [$r->equipo_id . '|' . substr((string) $r->fecha_original, 0, 10) => true])
            ->all();
    }

    private function lineas(Contrato $contrato): Collection
    {
        $planes = $contrato->planes()->get()->keyBy('tipo_equipo_id');

        return $contrato->lineas()->with('equipo.area')->get()->map(function ($linea) use ($planes, $contrato) {
            $equipo = $linea->equipo;
            if (! $equipo || ! $equipo->area) {
                return null;
            }

            $plan = $planes->get($equipo->tipo_equipo_id);
            $frecuencia = $linea->frecuencia ?? $plan?->frecuencia;
            if (! $frecuencia) {
                return null;
            }

            return [
                'linea' => $linea,
                'equipo_id' => $equipo->id,
                'sucursal_id' => $equipo->area->sucursal_id,
                'frecuencia' => $frecuencia,
                'inicio' => $linea->fecha_inicio ?? $contrato->vigencia_inicio,
                'anticipacion' => (int) ($plan?->anticipacion_dias ?? $contrato->anticipacion_dias),
            ];
        })->filter()->values();
    }
}
