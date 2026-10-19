<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class GeneradorCodigoEquipo
{
    public function siguiente(string $prefijo = 'EQ'): string
    {
        return DB::transaction(function () use ($prefijo) {
            DB::table('codigo_secuencias')->insertOrIgnore(['prefijo' => $prefijo, 'ultimo' => 0]);

            $fila = DB::table('codigo_secuencias')
                ->where('prefijo', $prefijo)
                ->lockForUpdate()
                ->first();

            $numero = $fila->ultimo + 1;
            DB::table('codigo_secuencias')->where('prefijo', $prefijo)->update(['ultimo' => $numero]);

            return sprintf('%s-%04d', $prefijo, $numero);
        });
    }
}
