<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\RegistraCreador;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlantillaAjusteCliente extends Model
{
    use Auditable, RegistraCreador;

    protected $table = 'plantilla_ajustes_cliente';
    protected $guarded = [];
    protected $casts = ['ajustes' => 'array', 'activo' => 'boolean'];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function plantilla(): BelongsTo
    {
        return $this->belongsTo(PlantillaFormulario::class, 'plantilla_id');
    }

    public function tipoEquipo(): BelongsTo
    {
        return $this->belongsTo(TipoEquipo::class, 'tipo_equipo_id');
    }
}
