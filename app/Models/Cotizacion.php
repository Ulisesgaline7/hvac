<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\RegistraCreador;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Cotizacion extends Model
{
    use Auditable, RegistraCreador, SoftDeletes;

    protected $table = 'cotizaciones';
    protected $guarded = [];

    protected $casts = [
        'fecha' => 'date',
        'valida_hasta' => 'date',
        'enviada_en' => 'datetime',
        'aprobada_en' => 'datetime',
        'recargos' => 'array',
        'logistica' => 'array',
        'logistica_detalle' => 'array',
        'descuento_pct' => 'decimal:4',
    ];

    protected static function booted(): void
    {
        static::creating(function (Cotizacion $c) {
            if (empty($c->numero)) {
                $c->numero = 'TMP-' . Str::uuid();
            }
            $c->fecha ??= now()->toDateString();
            $c->estado ??= 'borrador';
        });

        static::created(function (Cotizacion $c) {
            if (str_starts_with($c->numero, 'TMP-')) {
                $c->numero = sprintf('COT-%s-%06d', now()->format('Y'), $c->id);
                $c->saveQuietly();
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

    public function lineas(): HasMany
    {
        return $this->hasMany(CotizacionLinea::class, 'cotizacion_id');
    }

    public function equipos(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Equipo::class, 'cotizacion_equipo')->withTimestamps();
    }
}
