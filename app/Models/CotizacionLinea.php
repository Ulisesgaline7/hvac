<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CotizacionLinea extends Model
{
    protected $table = 'cotizacion_lineas';
    protected $guarded = [];

    protected $casts = ['cantidad' => 'decimal:2', 'precio_unitario' => 'decimal:2', 'importe' => 'decimal:2', 'cuenta_equipo' => 'boolean'];

    protected static function booted(): void
    {
        static::saving(function (CotizacionLinea $l) {
            $l->importe = round((float) $l->cantidad * (float) $l->precio_unitario, 2);
        });
    }

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class, 'cotizacion_id');
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(ServicioCatalogo::class, 'servicio_id');
    }
}
