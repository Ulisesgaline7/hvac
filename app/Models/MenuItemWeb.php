<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItemWeb extends Model
{
    protected $table = 'web_menu_items';

    protected $fillable = ['ubicacion', 'etiqueta', 'tipo', 'pagina_id', 'url', 'parent_id', 'nueva_pestana', 'boton', 'visible', 'orden'];

    protected $casts = ['nueva_pestana' => 'boolean', 'boton' => 'boolean', 'visible' => 'boolean'];

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            if (! $m->orden) {
                $m->orden = ((int) static::query()->where('ubicacion', $m->ubicacion)->max('orden')) + 10;
            }
        });
    }

    public function pagina(): BelongsTo
    {
        return $this->belongsTo(WebPagina::class, 'pagina_id');
    }

    public function padre(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function hijos(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /** Dirección final del enlace, o null si no debe mostrarse (p. ej. página sin publicar). */
    public function href(): ?string
    {
        if ($this->tipo === 'pagina') {
            $p = $this->pagina;

            return $p && $p->publicada ? url('/' . $p->slug) : null;
        }

        $u = trim((string) $this->url);
        if ($u === '') {
            return null;
        }

        // «#contacto» se vuelve «/#contacto» para que también funcione desde las otras páginas.
        return str_starts_with($u, '#') ? '/' . $u : $u;
    }
}
