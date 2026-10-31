<?php

use App\Support\PlantillasWeb;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('web_paginas', function (Blueprint $t) {
            $t->jsonb('bloques')->nullable();
            $t->boolean('es_inicio')->default(false);
            $t->boolean('mostrar_titulo')->default(true);
        });

        // Las páginas que ya tenían texto pasan a tener una sección de texto con ese contenido.
        foreach (DB::table('web_paginas')->whereNotNull('contenido')->get() as $p) {
            if (trim(strip_tags((string) $p->contenido)) === '') {
                continue;
            }
            DB::table('web_paginas')->where('id', $p->id)->update([
                'bloques' => json_encode([[
                    'type' => 'texto',
                    'data' => ['ancla' => null, 'fondo' => 'claro', 'titulo' => null, 'contenido' => $p->contenido],
                ]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
        }

        // Copia editable de la portada actual. Queda SIN publicar: el sitio sigue igual hasta que la revises y la publiques.
        if (! DB::table('web_paginas')->where('slug', 'inicio')->exists()) {
            $aj = DB::table('ajustes_web')->pluck('valor', 'clave');
            DB::table('web_paginas')->insert([
                'titulo' => 'Inicio',
                'slug' => 'inicio',
                'bloques' => json_encode(
                    PlantillasWeb::inicio($aj['hero_titulo'] ?? null, $aj['hero_texto'] ?? null),
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ),
                'publicada' => false,
                'es_inicio' => true,
                'mostrar_titulo' => false,
                'orden' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('web_paginas')->where('slug', 'inicio')->where('es_inicio', true)->delete();
        Schema::table('web_paginas', function (Blueprint $t) {
            $t->dropColumn(['bloques', 'es_inicio', 'mostrar_titulo']);
        });
    }
};
