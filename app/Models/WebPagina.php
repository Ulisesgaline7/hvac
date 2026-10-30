<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebPagina extends Model
{
    protected $table = 'web_paginas';

    protected $fillable = ['titulo', 'slug', 'contenido', 'imagen', 'meta_titulo', 'meta_descripcion', 'publicada', 'orden'];

    protected $casts = ['publicada' => 'boolean'];

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

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            if (! $m->orden) {
                $m->orden = ((int) static::query()->max('orden')) + 10;
            }
        });
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItemWeb::class, 'pagina_id');
    }
}
