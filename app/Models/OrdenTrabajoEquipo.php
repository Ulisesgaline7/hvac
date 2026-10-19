<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrdenTrabajoEquipo extends Model implements HasMedia
{
    use InteractsWithMedia;

    public function registerMediaConversions(?Media $media = null): void
    {
        // Versión reducida para el PDF: evita incrustar fotos de varios MB.
        $this->addMediaConversion('informe')->width(800)->nonQueued();
    }

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

    public function movimientos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MovimientoRefrigerante::class, 'orden_trabajo_equipo_id');
    }

    public function ficha(): HasOne
    {
        return $this->hasOne(FichaTecnica::class, 'orden_trabajo_equipo_id');
    }
}
