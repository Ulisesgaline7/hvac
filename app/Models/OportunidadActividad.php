<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OportunidadActividad extends Model
{
    protected $table = 'oportunidad_actividades';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['vence_en' => 'datetime', 'hecha_en' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (OportunidadActividad $a) {
            $a->creado_por ??= auth()->id();
            $a->responsable_id ??= auth()->id();
        });
    }

    public function oportunidad(): BelongsTo { return $this->belongsTo(Oportunidad::class); }
    public function responsable(): BelongsTo { return $this->belongsTo(User::class, 'responsable_id'); }
}
