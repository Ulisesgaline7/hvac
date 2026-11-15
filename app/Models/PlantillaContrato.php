<?php

namespace App\Models;

use App\Providers\RlsServiceProvider;
use Illuminate\Database\Eloquent\Model;

class PlantillaContrato extends Model
{
    protected $table = 'plantillas_contrato';

    protected $guarded = [];

    protected $casts = ['sla' => 'array', 'activa' => 'boolean'];

    /** Plantilla del plan (PLAN_*) o la de servicio único. Devuelve null si falla. */
    public static function paraPlan(?string $codigo): ?self
    {
        try {
            $clave = $codigo && str_starts_with($codigo, 'PLAN_') ? $codigo : 'SERVICIO_UNICO';

            return RlsServiceProvider::comoInterno(fn () => static::where('clave', $clave)->where('activa', true)->first());
        } catch (\Throwable $e) {
            return null;
        }
    }
}
