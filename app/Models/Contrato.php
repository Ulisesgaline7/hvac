<?php

namespace App\Models;

use App\Enums\EstadoContrato;
use App\Enums\TipoContrato;
use App\Models\Concerns\AislaPorCliente;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\RegistraCreador;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contrato extends Model
{
    use AislaPorCliente, Auditable, RegistraCreador, SoftDeletes;

    protected $table = 'contratos';
    protected $guarded = [];

    protected $casts = [
        'tipo' => TipoContrato::class,
        'estado' => EstadoContrato::class,
        'vigencia_inicio' => 'date',
        'vigencia_fin' => 'date',
        'sla' => 'array',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function lineas(): HasMany
    {
        return $this->hasMany(ContratoEquipo::class);
    }

    public function equipos(): BelongsToMany
    {
        return $this->belongsToMany(Equipo::class, 'contrato_equipo')
            ->withPivot(['frecuencia', 'fecha_inicio', 'proxima_fecha'])
            ->withTimestamps();
    }

    public function planes(): HasMany
    {
        return $this->hasMany(PlanPreventivo::class);
    }

    public function ordenes(): HasMany
    {
        return $this->hasMany(OrdenTrabajo::class);
    }

    protected static function restringirACliente(Builder $consulta, int $clienteId): void
    {
        $consulta->where('cliente_id', $clienteId);
    }
}
