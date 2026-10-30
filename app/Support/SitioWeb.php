<?php

namespace App\Support;

use App\Models\AjusteWeb;
use App\Models\MenuItemWeb;
use Illuminate\Support\Collection;
use Throwable;

final class SitioWeb
{
    public static function ajustes(): array
    {
        return AjusteWeb::valores();
    }

    public static function nombre(): string
    {
        return self::ajustes()['sitio_nombre'] ?? 'Control Total';
    }

    /** URL de una imagen subida desde el panel (disco público) o, si no hay, de la que viene en /public. */
    public static function imagen(?string $ruta, string $defecto): string
    {
        return filled($ruta) ? asset('storage/' . ltrim($ruta, '/')) : asset($defecto);
    }

    public static function tipoImagen(?string $ruta): string
    {
        return match (strtolower(pathinfo((string) $ruta, PATHINFO_EXTENSION))) {
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => 'image/png',
        };
    }

    /**
     * Elementos de primer nivel de un menú, cada uno con su relación «hijosVisibles».
     * Si el menú nunca se configuró (o la tabla aún no existe) devuelve los enlaces originales del sitio.
     */
    public static function menu(string $ubicacion): Collection
    {
        try {
            if (! MenuItemWeb::query()->where('ubicacion', $ubicacion)->exists()) {
                return self::menuPorDefecto($ubicacion);
            }

            $items = MenuItemWeb::query()->with('pagina')
                ->where('ubicacion', $ubicacion)->where('visible', true)
                ->orderBy('orden')->orderBy('id')->get()
                ->filter(fn (MenuItemWeb $i) => $i->href() !== null)->values();
        } catch (Throwable) {
            return self::menuPorDefecto($ubicacion);
        }

        $hijos = $items->whereNotNull('parent_id')->groupBy('parent_id');

        return $items->whereNull('parent_id')->values()->each(
            fn (MenuItemWeb $i) => $i->setRelation('hijosVisibles', $hijos->get($i->id, collect())->values())
        );
    }

    private static function menuPorDefecto(string $ubicacion): Collection
    {
        $enlaces = [
            ['Servicios', '/#servicios'], ['Cómo trabajamos', '/#proceso'],
            ['Por qué Control Total', '/#control'], ['Contacto', '/#contacto'], ['Ingresar', '/ingresar'],
        ];

        return collect($enlaces)->map(function ($e) use ($ubicacion) {
            $i = new MenuItemWeb([
                'ubicacion' => $ubicacion, 'etiqueta' => $e[0], 'tipo' => 'url', 'url' => $e[1],
                'boton' => $ubicacion === 'principal' && $e[0] === 'Ingresar',
            ]);
            $i->setRelation('hijosVisibles', collect());

            return $i;
        });
    }
}
