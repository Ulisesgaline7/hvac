<?php

use App\Support\PlantillasWeb;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $now = now();
        $ajustes = [
            ['tema', 'Diseño del sitio', 'Diseño', 'tema', 'clasico', 'Elige el diseño que ve todo el mundo. Para probar AirCon antes de publicarlo, entra al panel y abre tusitio.com/?tema=aircon (para salir de la prueba: ?tema=salir).'],
            ['topbar_mostrar', 'Mostrar la barra superior (AirCon)', 'Diseño', 'si_no', '1', 'La barra oscura de arriba con teléfono, correo y enlaces pequeños.'],
            ['color_primario', 'Color principal (AirCon)', 'Colores', 'color', null, 'Botones, enlaces e iconos. Vacío = el naranja original (#FF800F).'],
            ['color_secundario', 'Color secundario (AirCon)', 'Colores', 'color', null, 'Vacío = el azul original (#001064).'],
            ['color_claro', 'Color de fondos claros (AirCon)', 'Colores', 'color', null, 'Vacío = el gris claro original (#F6F7F8).'],
            ['color_oscuro', 'Color de la barra superior y el pie (AirCon)', 'Colores', 'color', null, 'Vacío = el azul oscuro original (#010A35).'],
            ['pie_descripcion', 'Descripción del pie de página (AirCon)', 'Identidad', 'texto', 'Instalamos, mantenemos y reparamos aire acondicionado, refrigeración y ventilación. Cada trabajo queda registrado y puedes darle seguimiento en línea.', 'Texto corto junto al logo del pie.'],
            ['twitter', 'X / Twitter', 'Redes sociales', 'texto', null, 'Enlace completo. Vacío = no se muestra.'],
            ['linkedin', 'LinkedIn', 'Redes sociales', 'texto', null, 'Enlace completo. Vacío = no se muestra.'],
        ];

        $orden = (int) DB::table('ajustes_web')->max('orden');
        foreach ($ajustes as [$clave, $etiqueta, $grupo, $tipo, $valor, $ayuda]) {
            DB::table('ajustes_web')->insertOrIgnore([
                'clave' => $clave, 'etiqueta' => $etiqueta, 'grupo' => $grupo, 'tipo' => $tipo,
                'valor' => $valor, 'ayuda' => $ayuda, 'orden' => ++$orden,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        // Portada del tema AirCon lista para editar. Queda sin publicar: tú decides cuándo usarla.
        if (! DB::table('web_paginas')->where('slug', 'inicio-aircon')->exists()) {
            DB::table('web_paginas')->insert([
                'titulo' => 'Inicio (AirCon)',
                'slug' => 'inicio-aircon',
                'bloques' => json_encode(PlantillasWeb::inicioAircon(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'publicada' => false,
                'es_inicio' => false,
                'mostrar_titulo' => false,
                'orden' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Cache::forget('ajustes_web');
    }

    public function down(): void
    {
        DB::table('ajustes_web')->whereIn('clave', [
            'tema', 'topbar_mostrar', 'color_primario', 'color_secundario', 'color_claro', 'color_oscuro',
            'pie_descripcion', 'twitter', 'linkedin',
        ])->delete();
        DB::table('web_paginas')->where('slug', 'inicio-aircon')->delete();
        Cache::forget('ajustes_web');
    }
};
