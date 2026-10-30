<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ajustes_web', function (Blueprint $t) {
            $t->string('tipo', 12)->default('texto'); // texto | imagen
        });

        Schema::create('web_paginas', function (Blueprint $t) {
            $t->id();
            $t->string('titulo', 160);
            $t->string('slug', 120)->unique();
            $t->text('contenido')->nullable();
            $t->string('imagen')->nullable();
            $t->string('meta_titulo', 160)->nullable();
            $t->string('meta_descripcion', 300)->nullable();
            $t->boolean('publicada')->default(false);
            $t->unsignedInteger('orden')->default(0);
            $t->timestamps();
        });

        Schema::create('web_menu_items', function (Blueprint $t) {
            $t->id();
            $t->string('ubicacion', 20)->default('principal'); // principal | pie
            $t->string('etiqueta', 80);
            $t->string('tipo', 10)->default('url'); // pagina | url
            $t->foreignId('pagina_id')->nullable()->constrained('web_paginas')->cascadeOnDelete();
            $t->string('url', 255)->nullable();
            $t->foreignId('parent_id')->nullable()->constrained('web_menu_items')->cascadeOnDelete();
            $t->boolean('nueva_pestana')->default(false);
            $t->boolean('boton')->default(false);
            $t->boolean('visible')->default(true);
            $t->unsignedInteger('orden')->default(0);
            $t->timestamps();
            $t->index(['ubicacion', 'orden']);
        });

        $now = now();

        // Identidad: se agregan después de los ajustes que ya existen.
        $ajustes = [
            ['sitio_nombre', 'Nombre del sitio', 'Identidad', 'texto', 'Control Total', 'Aparece en el título de cada página.'],
            ['seo_titulo', 'Título de la portada (Google)', 'Identidad', 'texto', 'Control Total | Refrigeración y aire acondicionado', 'Lo que se ve en la pestaña y en los resultados de búsqueda.'],
            ['seo_descripcion', 'Descripción de la portada (Google)', 'Identidad', 'texto', 'Instalación, mantenimiento y reparación de aire acondicionado, refrigeración y ventilación. Cotizaciones en línea, informes verificables y seguimiento de tus equipos.', 'Una o dos frases. Máximo 160 caracteres recomendado.'],
            ['logo_encabezado', 'Logo del encabezado', 'Identidad', 'imagen', null, 'Se ve sobre fondo oscuro. Vacío = se usa el logo actual.'],
            ['logo_pie', 'Logo del pie de página', 'Identidad', 'imagen', null, 'Vacío = se usa el logo actual.'],
            ['favicon', 'Favicon (icono de la pestaña)', 'Identidad', 'imagen', null, 'Cuadrado, PNG o SVG. Vacío = se usa el icono actual.'],
            ['pie_texto', 'Texto del pie de página', 'Identidad', 'texto', null, 'Vacío = «© año Control Total. Todos los derechos reservados.»'],
            ['facebook', 'Facebook', 'Redes sociales', 'texto', null, 'Enlace completo. Vacío = no se muestra.'],
            ['instagram', 'Instagram', 'Redes sociales', 'texto', null, 'Enlace completo. Vacío = no se muestra.'],
            ['tiktok', 'TikTok', 'Redes sociales', 'texto', null, 'Enlace completo. Vacío = no se muestra.'],
            ['youtube', 'YouTube', 'Redes sociales', 'texto', null, 'Enlace completo. Vacío = no se muestra.'],
        ];
        $orden = (int) DB::table('ajustes_web')->max('orden');
        foreach ($ajustes as [$clave, $etiqueta, $grupo, $tipo, $valor, $ayuda]) {
            DB::table('ajustes_web')->insert([
                'clave' => $clave, 'etiqueta' => $etiqueta, 'grupo' => $grupo, 'tipo' => $tipo,
                'valor' => $valor, 'ayuda' => $ayuda, 'orden' => ++$orden,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        // Menús: lo mismo que tenía la portada escrito a mano.
        $enlaces = [
            ['Servicios', '/#servicios'],
            ['Cómo trabajamos', '/#proceso'],
            ['Por qué Control Total', '/#control'],
            ['Contacto', '/#contacto'],
            ['Ingresar', route('equipo.ingreso', [], false)],
        ];
        foreach (['principal', 'pie'] as $ubicacion) {
            foreach ($enlaces as $i => [$etiqueta, $url]) {
                DB::table('web_menu_items')->insert([
                    'ubicacion' => $ubicacion, 'etiqueta' => $etiqueta, 'tipo' => 'url', 'url' => $url,
                    'boton' => $ubicacion === 'principal' && $etiqueta === 'Ingresar',
                    'visible' => true, 'orden' => ($i + 1) * 10,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        Cache::forget('ajustes_web');
    }

    public function down(): void
    {
        Schema::dropIfExists('web_menu_items');
        Schema::dropIfExists('web_paginas');
        DB::table('ajustes_web')->whereIn('clave', [
            'sitio_nombre', 'seo_titulo', 'seo_descripcion', 'logo_encabezado', 'logo_pie', 'favicon',
            'pie_texto', 'facebook', 'instagram', 'tiktok', 'youtube',
        ])->delete();
        Schema::table('ajustes_web', function (Blueprint $t) {
            $t->dropColumn('tipo');
        });
        Cache::forget('ajustes_web');
    }
};
