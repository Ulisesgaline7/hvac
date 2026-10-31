<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Throwable;

class WebPagina extends Model
{
    protected $table = 'web_paginas';

    protected $fillable = [
        'titulo', 'slug', 'contenido', 'bloques', 'imagen', 'meta_titulo', 'meta_descripcion',
        'publicada', 'es_inicio', 'mostrar_titulo', 'orden',
    ];

    protected $casts = [
        'publicada' => 'boolean', 'es_inicio' => 'boolean', 'mostrar_titulo' => 'boolean', 'bloques' => 'array',
    ];

    /** Direcciones que ya usa el sistema: una página no puede llamarse así. */
    public const RESERVADOS = [
        'admin', 'tecnico', 'portal', 'cliente', 'clientes', 'login', 'logout', 'register',
        'ingresar', 'contacto', 'etiquetas', 'equipos', 'ordenes-trabajo', 'cotizacion', 'cotizaciones',
        'facturas', 'pagos', 'api-tecnico', 'api', 'e', 'v', 'up', 'storage', 'livewire', 'filament',
        'brand', 'template', 'css', 'js', 'fonts', 'images', 'build', 'vendor',
    ];

    /** Patrón para la ruta /{slug}: una sola palabra válida y no reservada. */
    public static function patronRuta(): string
    {
        $reservados = implode('|', array_map(fn ($r) => preg_quote($r, '#'), self::RESERVADOS));

        return '(?!(?:' . $reservados . ')$)[a-z0-9]+(?:-[a-z0-9]+)*';
    }

    /** La página que se muestra como portada (/), si hay una publicada. */
    public static function inicio(): ?self
    {
        try {
            return static::query()->where('es_inicio', true)->where('publicada', true)->first();
        } catch (Throwable) {
            return null;
        }
    }

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            if (! $m->orden) {
                $m->orden = ((int) static::query()->max('orden')) + 10;
            }
        });

        // Solo una página puede ser la portada.
        static::saved(function (self $m) {
            if ($m->es_inicio) {
                static::query()->where('id', '!=', $m->id)->where('es_inicio', true)->update(['es_inicio' => false]);
            }
        });
    }

    /** Texto corto para la descripción de Google cuando no se escribió una. */
    public function resumen(): string
    {
        foreach (collect($this->bloques ?? [])->values() as $b) {
            $d = $b['data'] ?? [];
            foreach (['contenido', 'texto', 'intro'] as $campo) {
                if (filled($d[$campo] ?? null)) {
                    return Str::limit(trim(strip_tags((string) $d[$campo])), 160);
                }
            }
        }

        return Str::limit(trim(strip_tags((string) $this->contenido)), 160);
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItemWeb::class, 'pagina_id');
    }
}
