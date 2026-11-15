<?php

namespace App\Services;

use App\Models\Division;
use Illuminate\Support\Facades\DB;

class DivisionService
{
    /** @return array{0:int,1:int} reglas y servicios copiados */
    public static function copiarDesdeClima(Division $d): array
    {
        $clima = Division::where('codigo', 'CLIMA')->first();

        if (! $clima || $clima->id === $d->id) {
            throw new \DomainException('Elige una división distinta de CLIMA.');
        }

        return DB::transaction(function () use ($d, $clima) {
            if (DB::table('reglas_tarifario')->where('division_id', $d->id)->exists()
                || DB::table('servicios_catalogo')->where('division_id', $d->id)->exists()) {
                throw new \DomainException('Esta división ya tiene reglas o servicios; no se copia para no duplicar.');
            }

            $reglas = DB::affectingStatement(
                'insert into reglas_tarifario (division_id, clave, etiqueta, grupo, tipo, valor, ayuda, orden, created_at, updated_at)
                 select ?, clave, etiqueta, grupo, tipo, valor, ayuda, orden, now(), now()
                 from reglas_tarifario where division_id = ?',
                [$d->id, $clima->id]
            );

            $servicios = DB::affectingStatement(
                'insert into servicios_catalogo (codigo, categoria, nombre, detalle, precio, precio_hasta, cuenta_equipo, activo, division_id, created_at, updated_at)
                 select ?::text || codigo, categoria, nombre, detalle, precio, precio_hasta, cuenta_equipo, false, ?, now(), now()
                 from servicios_catalogo where division_id = ?',
                [$d->codigo . '-', $d->id, $clima->id]
            );

            \Illuminate\Support\Facades\Cache::forget('reglas_tarifario:' . $d->id);

            return [$reglas, $servicios];
        });
    }
}
