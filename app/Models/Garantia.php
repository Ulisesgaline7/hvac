<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\RegistraCreador;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Garantia extends Model
{
    use Auditable, RegistraCreador;

    protected $table = 'garantias';
    protected $guarded = [];

    protected $casts = ['desde' => 'date', 'hasta' => 'date'];

    protected static function booted(): void
    {
        static::creating(function (Garantia $g) {
            if (empty($g->numero)) {
                $g->numero = 'TMP-' . Str::uuid();
            }
        });

        static::created(function (Garantia $g) {
            if (str_starts_with($g->numero, 'TMP-')) {
                $g->numero = sprintf('GAR-%s-%06d', now()->format('Y'), $g->id);
                $g->saveQuietly();
            }
        });
    }

    public function ordenTrabajo(): BelongsTo
    {
        return $this->belongsTo(OrdenTrabajo::class, 'orden_trabajo_id');
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function estaVigente(): bool
    {
        return $this->estado === 'vigente' && $this->hasta->endOfDay()->isFuture();
    }
}
