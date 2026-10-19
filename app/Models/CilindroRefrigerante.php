<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\RegistraCreador;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CilindroRefrigerante extends Model
{
    use Auditable, RegistraCreador, SoftDeletes;

    protected $table = 'cilindros_refrigerante';
    protected $guarded = [];
    protected $casts = ['peso_inicial_kg' => 'decimal:2'];

    public function refrigerante(): BelongsTo
    {
        return $this->belongsTo(Refrigerante::class);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoRefrigerante::class, 'cilindro_id');
    }

    /** Saldo = inicial − cargas + recuperaciones. Nunca se guarda. */
    public function saldoKg(): float
    {
        $cargas = (float) $this->movimientos()->where('tipo', 'carga')->sum('cantidad_kg');
        $recuperado = (float) $this->movimientos()->where('tipo', 'recuperacion')->sum('cantidad_kg');

        return round((float) $this->peso_inicial_kg - $cargas + $recuperado, 2);
    }
}
