<?php

namespace App\Models;

use App\Models\Concerns\PerteneceADivision;
use App\Models\Concerns\RegistraCreador;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Oportunidad extends Model
{
    use SoftDeletes, PerteneceADivision, RegistraCreador;

    protected $table = 'oportunidades';
    protected $guarded = [];

    public const ETAPAS = [
        'nuevo' => 'Nuevo',
        'contactado' => 'Contactado',
        'levantamiento' => 'Visita / levantamiento',
        'cotizado' => 'Cotizado',
        'negociacion' => 'Negociación',
        'ganada' => 'Ganada',
        'perdida' => 'Perdida',
    ];

    protected function casts(): array
    {
        return ['cierre_esperado' => 'date', 'cerrada_en' => 'datetime', 'monto_estimado' => 'decimal:2'];
    }

    protected static function booted(): void
    {
        static::saving(function (Oportunidad $o) {
            if (in_array($o->etapa, ['ganada', 'perdida'], true)) {
                $o->cerrada_en ??= now();
                $o->probabilidad = $o->etapa === 'ganada' ? 100 : 0;
            } else {
                $o->cerrada_en = null;
            }
        });
    }

    public function cliente(): BelongsTo { return $this->belongsTo(Cliente::class); }
    public function responsable(): BelongsTo { return $this->belongsTo(User::class, 'responsable_id'); }
    public function actividades(): HasMany { return $this->hasMany(OportunidadActividad::class); }
    public function cotizaciones(): HasMany { return $this->hasMany(Cotizacion::class); }

    public function getNombreProspectoAttribute(): string
    {
        return $this->cliente?->razon_social ?? $this->prospecto_nombre ?? '—';
    }
}
