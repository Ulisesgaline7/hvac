<?php

use App\Models\MenuItemWeb;
use App\Models\WebPagina;
use App\Support\PlantillasWeb;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;

/**
 * Convierte el sitio en multipágina: crea Nosotros, Servicios, Preguntas frecuentes y Contacto
 * (publicadas, con título visible) y arma el menú. No toca la portada ni páginas que ya existan.
 * Si el menú ya fue configurado desde el panel, tampoco lo modifica.
 */
return new class extends Migration {
    public function up(): void
    {
        $paginas = [];

        foreach (PlantillasWeb::paginasIniciales() as [$titulo, $slug, $plantilla, $descripcion]) {
            $paginas[$slug] = WebPagina::query()->where('slug', $slug)->first()
                ?? WebPagina::create([
                    'titulo' => $titulo,
                    'slug' => $slug,
                    'bloques' => PlantillasWeb::bloques($plantilla),
                    'meta_descripcion' => $descripcion,
                    'publicada' => true,
                    'es_inicio' => false,
                    'mostrar_titulo' => true,
                ]);
        }

        $this->armarMenu('principal', $paginas, true);
        $this->armarMenu('pie', $paginas, false);

        Cache::forget('ajustes_web');
    }

    public function down(): void
    {
        $paginas = WebPagina::query()->whereIn('slug', array_column(PlantillasWeb::paginasIniciales(), 1))->get();

        MenuItemWeb::query()
            ->whereIn('ubicacion', ['principal', 'pie'])
            ->where(function ($q) use ($paginas) {
                $q->whereIn('pagina_id', $paginas->pluck('id'))
                    ->orWhere(fn ($q2) => $q2->where('tipo', 'url')->whereIn('url', ['/', '/ingresar']));
            })->delete();

        WebPagina::query()->whereIn('id', $paginas->pluck('id'))->delete();
    }

    /** Solo si ese menú nunca se configuró (así se respeta lo que ya hayas armado en el panel). */
    private function armarMenu(string $ubicacion, array $paginas, bool $conInicio): void
    {
        if (MenuItemWeb::query()->where('ubicacion', $ubicacion)->exists()) {
            return;
        }

        $etiquetas = [
            'nosotros' => 'Nosotros',
            'servicios' => 'Servicios',
            'preguntas-frecuentes' => 'Preguntas frecuentes',
            'contactanos' => 'Contacto',
        ];

        if ($conInicio) {
            $this->item($ubicacion, 'Inicio', 'url', null, '/', false);
        }

        foreach ($etiquetas as $slug => $etiqueta) {
            if (isset($paginas[$slug])) {
                $this->item($ubicacion, $etiqueta, 'pagina', $paginas[$slug]->id, null, false);
            }
        }

        $this->item($ubicacion, 'Ingresar', 'url', null, '/ingresar', $ubicacion === 'principal');
    }

    private function item(string $ubicacion, string $etiqueta, string $tipo, ?int $paginaId, ?string $url, bool $boton): void
    {
        MenuItemWeb::create([
            'ubicacion' => $ubicacion,
            'etiqueta' => $etiqueta,
            'tipo' => $tipo,
            'pagina_id' => $paginaId,
            'url' => $url,
            'nueva_pestana' => false,
            'boton' => $boton,
            'visible' => true,
        ]);
    }
};
