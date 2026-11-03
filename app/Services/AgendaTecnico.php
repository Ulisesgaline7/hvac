<?php

namespace App\Services;

use App\Models\OrdenTrabajo;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Agenda de un técnico: qué OT abiertas tiene un día y si una nueva choca con ellas.
 * Cuenta tanto las OT donde es líder (tecnico_id) como las de su cuadrilla.
 */
class AgendaTecnico
{
    /** OT abiertas del técnico en un día, ordenadas por hora. */
    public static function delDia(int $tecnicoId, string $fecha, ?int $ignorarOtId = null): Collection
    {
        return OrdenTrabajo::query()
            ->abiertas()
            ->whereDate('fecha_programada', substr($fecha, 0, 10))
            ->when($ignorarOtId, fn ($q) => $q->whereKeyNot($ignorarOtId))
            ->where(function ($q) use ($tecnicoId) {
                $q->where('tecnico_id', $tecnicoId)
                    ->orWhereHas('cuadrilla', fn ($c) => $c->where('tecnicos.id', $tecnicoId));
            })
            ->orderBy('hora_programada')
            ->get();
    }

    /**
     * OT del técnico cuyo horario se traslapa con desde la hora hasta hora + duración.
     * Sin hora no hay choque «duro»: solo se informa la carga del día con delDia().
     */
    public static function choques(int $tecnicoId, string $fecha, ?string $hora, ?int $duracionMin = null, ?int $ignorarOtId = null): Collection
    {
        if (blank($hora)) {
            return collect();
        }

        $dia = substr($fecha, 0, 10);
        $inicio = Carbon::parse($dia . ' ' . $hora);
        $fin = $inicio->copy()->addMinutes($duracionMin ?: (int) config('sla.duracion_default_min', 120));

        return static::delDia($tecnicoId, $dia, $ignorarOtId)
            ->filter(function (OrdenTrabajo $otro) use ($dia, $inicio, $fin) {
                if (blank($otro->hora_programada)) {
                    return false;
                }

                $otroIni = Carbon::parse($dia . ' ' . $otro->hora_programada);
                $otroFin = $otroIni->copy()->addMinutes($otro->duracion_estimada_min ?: (int) config('sla.duracion_default_min', 120));

                return $inicio->lt($otroFin) && $otroIni->lt($fin);
            })
            ->values();
    }
}
