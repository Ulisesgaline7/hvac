<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AceptacionTecnico extends Model
{
    protected $table = 'aceptaciones_tecnico';
    protected $guarded = [];
    protected $casts = ['aceptado_at' => 'datetime'];

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(Tecnico::class);
    }

    public function documento(): BelongsTo
    {
        return $this->belongsTo(DocumentoCatalogo::class, 'documento_id');
    }
}
