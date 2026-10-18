<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrdenTrabajoEquipo extends Model
{
    protected $table = 'orden_trabajo_equipo';
    protected $guarded = [];

    protected $casts = ['datos_previstos' => 'array'];

    public function ordenTrabajo(): BelongsTo
    {
        return $this->belongsTo(OrdenTrabajo::class, 'orden_trabajo_id');
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function ficha(): HasOne
    {
        return $this->hasOne(FichaTecnica::class, 'orden_trabajo_equipo_id');
    }
}
