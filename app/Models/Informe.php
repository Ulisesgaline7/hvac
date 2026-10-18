<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\RegistraCreador;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Informe extends Model
{
    use Auditable, RegistraCreador;

    protected $table = 'informes';
    protected $guarded = [];

    protected $casts = ['generado_en' => 'datetime', 'publicado_en' => 'datetime'];

    public function ordenTrabajo(): BelongsTo
    {
        return $this->belongsTo(OrdenTrabajo::class, 'orden_trabajo_id');
    }

    public function estaPublicado(): bool
    {
        return $this->estado === 'publicado';
    }
}
