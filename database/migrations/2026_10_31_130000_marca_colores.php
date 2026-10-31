<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** El tema AirCon ahora trae la paleta de Control Total; actualiza la ayuda de los selectores de color. */
return new class extends Migration {
    public function up(): void
    {
        $ayudas = [
            'color_primario' => 'Botones, enlaces e iconos. Vacío = el azul de la marca (#087BFF).',
            'color_secundario' => 'Vacío = el azul medio de la marca (#0D3B66).',
            'color_claro' => 'Vacío = el gris azulado claro de la marca (#F4F8FC).',
            'color_oscuro' => 'Barra superior y pie. Vacío = el azul oscuro de la marca (#082A4A).',
        ];

        foreach ($ayudas as $clave => $ayuda) {
            DB::table('ajustes_web')->where('clave', $clave)->update(['ayuda' => $ayuda]);
        }

        Cache::forget('ajustes_web');
    }

    public function down(): void
    {
        // Solo cambia textos de ayuda.
    }
};
