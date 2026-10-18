<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\AislaPorCliente;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Models\Concerns\RegistraCreador;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sucursal extends Model
{
    use AislaPorCliente, Auditable, RegistraCreador, SoftDeletes;

    protected $table = 'sucursales';
    protected $guarded = [];
    protected $casts = ['contactos' => 'array'];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function areas(): HasMany
    {
        return $this->hasMany(Area::class);
    }

    protected static function restringirACliente(Builder $consulta, int $clienteId): void
    {
        $consulta->where('cliente_id', $clienteId);
    }
}
