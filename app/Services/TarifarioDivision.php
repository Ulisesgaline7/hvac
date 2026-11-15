<?php

namespace App\Services;

use App\Models\ReglaTarifario;

/** Tarifario de una división: el archivo config/tarifario.php, pisado por las reglas de esa división. */
class TarifarioDivision
{
    public static function para(?int $divisionId): array
    {
        $cfg = require config_path('tarifario.php');

        if (! $divisionId) {
            return $cfg;
        }

        foreach (ReglaTarifario::guardadas($divisionId) as $clave => $valor) {
            $cfg[$clave] = ReglaTarifario::paraConfig($clave, $valor);
        }

        return $cfg;
    }
}
