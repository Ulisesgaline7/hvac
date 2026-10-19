<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\RegistraCreador;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    use Auditable, RegistraCreador;

    protected $table = 'pagos';
    protected $guarded = [];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Pago $p) {
            $p->fecha ??= now()->toDateString();
            $p->tipo ??= 'pago';
        });

        static::saved(fn (Pago $p) => $p->factura?->recalcular());
        static::deleted(fn (Pago $p) => $p->factura?->recalcular());
    }

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class);
    }

    public function ordenTrabajo(): BelongsTo
    {
        return $this->belongsTo(OrdenTrabajo::class, 'orden_trabajo_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
