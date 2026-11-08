<?php

namespace App\Support;

/** Actividades de checklist de una línea de OT: una por renglón de «tareas previstas». */
class ChecklistLinea
{
    /** @return array<string,string> clave estable (hash del texto) => texto */
    public static function actividades($linea): array
    {
        $salida = [];
        foreach (preg_split('/\R/', (string) ($linea->datos_previstos['tareas'] ?? '')) as $renglon) {
            $renglon = trim($renglon);
            if ($renglon !== '') {
                $salida[substr(md5($renglon), 0, 8)] = $renglon;
            }
        }

        return $salida;
    }

    /** Respuestas guardadas que corresponden a actividades vigentes. */
    public static function respuestas($linea): array
    {
        return array_intersect_key((array) ($linea->datos_previstos['checklist'] ?? []), self::actividades($linea));
    }

    public static function pendientes($linea): int
    {
        $hechas = count(array_filter(self::respuestas($linea), fn ($r) => ! empty($r['estado'])));

        return count(self::actividades($linea)) - $hechas;
    }

    public static function fallas($linea): array
    {
        return array_values(array_filter(self::respuestas($linea), fn ($r) => ($r['estado'] ?? null) === 'falla'));
    }
}
