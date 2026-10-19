<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\RegistraCreador;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActaConformidad extends Model implements HasMedia
{
    use Auditable, InteractsWithMedia, RegistraCreador;

    protected $table = 'actas_conformidad';
    protected $guarded = [];

    protected $casts = [
        'fecha' => 'date',
        'confirmaciones' => 'array',
        'calificacion' => 'integer',
    ];

    public function ordenTrabajo(): BelongsTo
    {
        return $this->belongsTo(OrdenTrabajo::class, 'orden_trabajo_id');
    }
}
