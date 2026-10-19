<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\RegistraCreador;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoRefrigerante extends Model
{
    use Auditable, RegistraCreador;

    protected $table = 'movimientos_refrigerante';
    protected $guarded = [];
    protected $casts = ['cantidad_kg' => 'decimal:2', 'registrado_en' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            if ($m->tipo === 'carga' && $m->cilindro_id) {
                $saldo = CilindroRefrigerante::findOrFail($m->cilindro_id)->saldoKg();
                if ((float) $m->cantidad_kg > $saldo) {
                    throw new DomainException("El cilindro solo tiene {$saldo} kg disponibles.");
                }
            }
        });
    }

    public function cilindro(): BelongsTo
    {
        return $this->belongsTo(CilindroRefrigerante::class, 'cilindro_id');
    }

    public function linea(): BelongsTo
    {
        return $this->belongsTo(OrdenTrabajoEquipo::class, 'orden_trabajo_equipo_id');
    }
}
