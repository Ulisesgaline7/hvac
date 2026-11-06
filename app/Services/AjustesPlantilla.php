<?php

namespace App\Services;

use App\Models\PlantillaAjusteCliente;

/**
 * Aplica los ajustes de un cliente sobre la definición base de una plantilla.
 *
 * Formato de "ajustes":
 *  ocultar:     ["clave", ...]
 *  obligatorio: {"clave": true|false}
 *  rangos:      {"clave": {"min": 1, "max": 9}}      (solo campos tipo numero)
 *  agregar:     [{"tipo":"numero","clave":"...","etiqueta":"...","obligatorio":true,"despues":"clave_existente"}]
 */
class AjustesPlantilla
{
    /** Devuelve la definición final, o null si el cliente no tiene ajustes para esta plantilla. */
    public function definicionPara(int $clienteId, int $plantillaId, array $base): ?array
    {
        $ajustes = PlantillaAjusteCliente::where('cliente_id', $clienteId)
            ->where('activo', true)
            ->where('ambito', 'ficha')
            ->where(fn ($q) => $q->whereNull('plantilla_id')->orWhere('plantilla_id', $plantillaId))
            ->orderByRaw('plantilla_id is not null') // primero los generales, luego los específicos
            ->get();

        if ($ajustes->isEmpty()) {
            return null;
        }

        $campos = $base;
        foreach ($ajustes as $a) {
            $campos = $this->aplicar($campos, $a->ajustes ?? []);
        }

        return $campos;
    }

    /** Campos de registro de un equipo: base del tipo + ajustes del cliente (generales y por tipo). */
    public function camposRegistro(int $clienteId, int $tipoEquipoId, array $base): array
    {
        $ajustes = PlantillaAjusteCliente::where('cliente_id', $clienteId)
            ->where('activo', true)
            ->where('ambito', 'registro')
            ->where(fn ($q) => $q->whereNull('tipo_equipo_id')->orWhere('tipo_equipo_id', $tipoEquipoId))
            ->orderByRaw('tipo_equipo_id is not null')
            ->get();

        $campos = $base;
        foreach ($ajustes as $a) {
            $campos = $this->aplicar($campos, $a->ajustes ?? []);
        }

        return $campos;
    }

    public function aplicar(array $campos, array $a): array
    {
        $ocultar = $a['ocultar'] ?? [];
        $campos = array_values(array_filter($campos, fn ($c) => ! in_array($c['clave'], $ocultar, true)));

        foreach ($campos as &$c) {
            if (array_key_exists($c['clave'], $a['obligatorio'] ?? [])) {
                $c['obligatorio'] = (bool) $a['obligatorio'][$c['clave']];
            }
            if (($c['tipo'] ?? null) === 'numero' && isset($a['rangos'][$c['clave']])) {
                $r = $a['rangos'][$c['clave']];
                $c['min'] = $r['min'] ?? ($c['min'] ?? null);
                $c['max'] = $r['max'] ?? ($c['max'] ?? null);
            }
        }
        unset($c);

        foreach ($a['agregar'] ?? [] as $nuevo) {
            if (empty($nuevo['clave']) || empty($nuevo['tipo']) || empty($nuevo['etiqueta'])) {
                continue;
            }
            if (collect($campos)->contains('clave', $nuevo['clave'])) {
                continue; // no duplicar claves
            }
            $despues = $nuevo['despues'] ?? null;
            unset($nuevo['despues']);

            $pos = $despues ? collect($campos)->search(fn ($c) => $c['clave'] === $despues) : false;
            $pos === false
                ? $campos[] = $nuevo
                : array_splice($campos, $pos + 1, 0, [$nuevo]);
        }

        return array_values($campos);
    }
}
