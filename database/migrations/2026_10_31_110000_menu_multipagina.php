<?php

use App\Models\MenuItemWeb;
use App\Models\WebPagina;
use Illuminate\Database\Migrations\Migration;

/**
 * Reemplaza los menús «principal» y «pie» por los de la versión multipágina,
 * pero SOLO si todavía son los enlaces originales (sin personalizar). Si ya los editaste, no los toca.
 */
return new class extends Migration {
    private const ORIGINALES = ['Servicios', 'Cómo trabajamos', 'Por qué Control Total', 'Contacto', 'Ingresar'];

    public function up(): void
    {
        $paginas = WebPagina::query()
            ->whereIn('slug', ['nosotros', 'servicios', 'preguntas-frecuentes', 'contactanos'])
            ->where('publicada', true)->get()->keyBy('slug');

        foreach (['principal' => true, 'pie' => false] as $ubicacion => $conInicio) {
            if (! $this->sinPersonalizar($ubicacion)) {
                continue;
            }

            MenuItemWeb::query()->where('ubicacion', $ubicacion)->delete();

            if ($conInicio) {
                $this->item($ubicacion, 'Inicio', 'url', null, '/', false);
            }

            foreach (['nosotros' => 'Nosotros', 'servicios' => 'Servicios', 'preguntas-frecuentes' => 'Preguntas frecuentes', 'contactanos' => 'Contacto'] as $slug => $etiqueta) {
                if ($paginas->has($slug)) {
                    $this->item($ubicacion, $etiqueta, 'pagina', $paginas[$slug]->id, null, false);
                }
            }

            $this->item($ubicacion, 'Ingresar', 'url', null, '/ingresar', $ubicacion === 'principal');
        }
    }

    public function down(): void
    {
        // No se restauran los menús anteriores: se pueden volver a armar desde el panel.
    }

    private function sinPersonalizar(string $ubicacion): bool
    {
        $items = MenuItemWeb::query()->where('ubicacion', $ubicacion)->get();

        return $items->every(fn (MenuItemWeb $i) => $i->tipo === 'url'
            && $i->parent_id === null
            && in_array($i->etiqueta, self::ORIGINALES, true));
    }

    private function item(string $ubicacion, string $etiqueta, string $tipo, ?int $paginaId, ?string $url, bool $boton): void
    {
        MenuItemWeb::create([
            'ubicacion' => $ubicacion, 'etiqueta' => $etiqueta, 'tipo' => $tipo, 'pagina_id' => $paginaId,
            'url' => $url, 'nueva_pestana' => false, 'boton' => $boton, 'visible' => true,
        ]);
    }
};
