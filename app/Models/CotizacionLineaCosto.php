<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CotizacionLineaCosto extends Model
{
    protected $table = 'cotizacion_linea_costos';
    protected $guarded = [];

    protected $casts = ['costo_unitario' => 'decimal:2'];

    public function linea(): BelongsTo
    {
        return $this->belongsTo(CotizacionLinea::class, 'cotizacion_linea_id');
    }
}
