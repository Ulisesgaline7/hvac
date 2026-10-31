<?php

use App\Models\WebPagina;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * AirCon queda como único diseño: se quita el ajuste «Diseño del sitio» y la portada AirCon pasa a ser la portada
 * editable (solo si todavía no hay otra portada publicada).
 */
return new class extends Migration {
    public function up(): void
    {
        DB::table('ajustes_web')->where('clave', 'tema')->delete();

        $hayPortada = WebPagina::query()->where('es_inicio', true)->where('publicada', true)->exists();
        $aircon = WebPagina::query()->where('slug', 'inicio-aircon')->first();

        if (! $hayPortada && $aircon) {
            // La copia de la portada del diseño anterior queda aparte, sin publicar.
            WebPagina::query()->where('slug', 'inicio')->where('publicada', false)
                ->update(['titulo' => 'Inicio (diseño anterior)']);

            $aircon->update(['titulo' => 'Inicio', 'publicada' => true, 'es_inicio' => true, 'mostrar_titulo' => false]);
        }

        Cache::forget('ajustes_web');
    }

    public function down(): void
    {
        // No se restaura el diseño anterior.
    }
};
