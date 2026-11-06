<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoTecnico extends Model
{
    protected $table = 'pagos_tecnico';
    protected $guarded = [];

    protected $casts = ['aprobado_en' => 'datetime'];

    public function ordenTrabajo(): BelongsTo
    {
        return $this->belongsTo(OrdenTrabajo::class, 'orden_trabajo_id');
    }

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(Tecnico::class);
    }

    public function liquidacion(): BelongsTo
    {
        return $this->belongsTo(LiquidacionTecnico::class, 'liquidacion_id');
    }
}
