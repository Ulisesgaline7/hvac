<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\RegistraCreador;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Factura extends Model
{
    use Auditable, RegistraCreador;

    protected $table = 'facturas';
    protected $guarded = [];

    protected $casts = [
        'fecha' => 'date',
        'vence' => 'date',
        'periodo' => 'date',
        'fecha_limite_emision' => 'date',
        'subtotal' => 'decimal:2',
        'isv' => 'decimal:2',
        'total' => 'decimal:2',
        'pagado' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Factura $f) {
            if (empty($f->numero)) {
                $f->numero = 'TMP-' . Str::uuid();
            }
            $f->fecha ??= now()->toDateString();
            $f->estado ??= 'emitida';
        });

        static::created(function (Factura $f) {
            if (str_starts_with($f->numero, 'TMP-')) {
                app(config('cobros.numerador'))->asignar($f);
                $f->saveQuietly();
            }
        });
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function ordenTrabajo(): BelongsTo
    {
        return $this->belongsTo(OrdenTrabajo::class, 'orden_trabajo_id');
    }

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class);
    }

    public function cuotas(): HasMany
    {
        return $this->hasMany(FacturaCuota::class)->orderBy('numero');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }

    public function getSaldoAttribute(): float
    {
        return round((float) $this->total - (float) $this->pagado, 2);
    }

    /** Sin plan: vence la factura. Con plan: alguna cuota sin cubrir ya pasó su fecha. */
    public function getVencidaAttribute(): bool
    {
        if (in_array($this->estado, ['pagada', 'anulada'], true) || $this->saldo <= 0) {
            return false;
        }

        if ($this->cuotas()->exists()) {
            return app(\App\Services\PlanCuotasService::class)->estado($this)
                ->contains(fn ($fila) => $fila['estado'] === 'vencida');
        }

        return $this->vence->lt(now()->startOfDay());
    }

    /** Recalcula lo pagado y el estado a partir de los pagos. */
    public function recalcular(): void
    {
        $pagado = round((float) $this->pagos()->sum('monto'), 2);
        $estado = $this->estado;

        if ($estado !== 'anulada') {
            $estado = $pagado >= (float) $this->total && (float) $this->total > 0
                ? 'pagada'
                : ($pagado > 0 ? 'parcial' : 'emitida');
        }

        $this->forceFill(['pagado' => $pagado, 'estado' => $estado])->saveQuietly();
    }
}
