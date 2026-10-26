<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AjusteWeb extends Model
{
    protected $table = 'ajustes_web';

    protected $fillable = ['clave', 'etiqueta', 'grupo', 'valor', 'ayuda', 'orden'];

    public static function valores(): array
    {
        return Cache::remember('ajustes_web', 300, fn () => static::query()->pluck('valor', 'clave')->all());
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('ajustes_web'));
        static::deleted(fn () => Cache::forget('ajustes_web'));
    }
}
