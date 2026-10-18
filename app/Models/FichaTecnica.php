<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\RegistraCreador;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FichaTecnica extends Model
{
    use Auditable, RegistraCreador;

    protected $table = 'fichas_tecnicas';
    protected $guarded = [];

    protected $casts = ['respuestas' => 'array', 'alertas' => 'array', 'completa' => 'boolean'];

    public function version(): BelongsTo
    {
        return $this->belongsTo(PlantillaVersion::class, 'plantilla_version_id');
    }

    public function linea(): BelongsTo
    {
        return $this->belongsTo(OrdenTrabajoEquipo::class, 'orden_trabajo_equipo_id');
    }
}
