<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlantillaVersion extends Model
{
    public const UPDATED_AT = null;
    public const CREATED_AT = null;

    protected $table = 'plantilla_versiones';
    protected $guarded = [];

    protected $casts = ['definicion' => 'array', 'publicada_en' => 'datetime'];

    public function plantilla(): BelongsTo
    {
        return $this->belongsTo(PlantillaFormulario::class, 'plantilla_id');
    }
}
