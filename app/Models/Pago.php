<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\RegistraCreador;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
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
            $p->numero ??= 'TMP-' . Str::uuid();
        });

        static::created(function (Pago $p) {
            if (str_starts_with((string) $p->numero, 'TMP-')) {
                $p->numero = sprintf('REC-%s-%06d', now()->format('Y'), $p->id);
                $p->saveQuietly();
            }
        });

        static::saved(function (Pago $p) {
            $p->factura?->recalcular();

            // Cobro sin crédito: la factura nace al completarse el pago (cobros.momento_factura).
            if (! $p->factura_id && $p->orden_trabajo_id && $p->wasRecentlyCreated) {
                app(\App\Services\FacturacionService::class)->facturarSiPagoCompleto($p->ordenTrabajo);
            }
        });
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
