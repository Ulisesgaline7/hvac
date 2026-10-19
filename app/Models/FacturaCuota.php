<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacturaCuota extends Model
{
    protected $table = 'factura_cuotas';
    protected $guarded = [];

    protected $casts = [
        'vence' => 'date',
        'monto' => 'decimal:2',
    ];

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class);
    }
}
