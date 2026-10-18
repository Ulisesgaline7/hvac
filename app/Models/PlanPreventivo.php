<?php

namespace App\Models;

use App\Enums\Frecuencia;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\RegistraCreador;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanPreventivo extends Model
{
    use Auditable, RegistraCreador;

    protected $table = 'planes_preventivos';
    protected $guarded = [];

    protected $casts = ['frecuencia' => Frecuencia::class];

    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class);
    }

    public function tipoEquipo(): BelongsTo
    {
        return $this->belongsTo(TipoEquipo::class, 'tipo_equipo_id');
    }
}
