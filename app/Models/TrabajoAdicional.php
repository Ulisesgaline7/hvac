<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\RegistraCreador;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrabajoAdicional extends Model implements HasMedia
{
    use Auditable, InteractsWithMedia, RegistraCreador;

    public const ISV = 0.15;

    protected $table = 'trabajos_adicionales';
    protected $guarded = [];

    protected $casts = [
        'fecha' => 'date',
        'nueva_fecha' => 'date',
        'detalle_costo' => 'array',
        'decidido_en' => 'datetime',
        'subtotal' => 'decimal:2',
        'isv' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (TrabajoAdicional $t) {
            $subtotal = collect($t->detalle_costo ?? [])
                ->sum(fn ($l) => (float) ($l['cantidad'] ?? 0) * (float) ($l['precio_unitario'] ?? 0));

            $t->subtotal = round($subtotal, 2);
            $t->isv = round($subtotal * self::ISV, 2);
            $t->total = round($subtotal * (1 + self::ISV), 2);

            if ($t->decision !== 'pendiente' && $t->decidido_en === null) {
                $t->decidido_en = now();
            }
        });
    }

    public function ordenTrabajo(): BelongsTo
    {
        return $this->belongsTo(OrdenTrabajo::class, 'orden_trabajo_id');
    }

    public function linea(): BelongsTo
    {
        return $this->belongsTo(OrdenTrabajoEquipo::class, 'orden_trabajo_equipo_id');
    }
}
