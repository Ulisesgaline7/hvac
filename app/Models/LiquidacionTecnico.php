<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LiquidacionTecnico extends Model
{
    protected $table = 'liquidaciones_tecnico';
    protected $guarded = [];

    protected $casts = [
        'semana_inicio' => 'date',
        'semana_fin' => 'date',
        'bruto' => 'decimal:2',
        'retencion_pct' => 'decimal:2',
        'retencion' => 'decimal:2',
        'neto' => 'decimal:2',
        'pagada_en' => 'datetime',
        'anulada_en' => 'datetime',
    ];

    /** Una liquidación con número asignado solo admite que se anule. */
    protected static function booted(): void
    {
        static::updating(function (LiquidacionTecnico $l) {
            if (blank($l->getOriginal('numero'))) {
                return;
            }
            $permitidos = ['estado', 'anulada_en', 'motivo_anulacion', 'updated_at'];
            $extra = array_diff(array_keys($l->getDirty()), $permitidos);
            if ($extra !== []) {
                throw new DomainException('El comprobante ya fue emitido y no se puede editar. Anúlelo y emita uno nuevo.');
            }
        });
    }

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(Tecnico::class);
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(PagoTecnico::class, 'liquidacion_id');
    }
}
