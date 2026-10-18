<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\AislaPorCliente;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Models\Concerns\RegistraCreador;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends Model
{
    use AislaPorCliente, Auditable, RegistraCreador, SoftDeletes;

    protected $table = 'clientes';
    protected $guarded = [];
    protected $casts = ['contactos' => 'array'];

    public function sucursales(): HasMany
    {
        return $this->hasMany(Sucursal::class);
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }

    protected static function restringirACliente(Builder $consulta, int $clienteId): void
    {
        $consulta->where('id', $clienteId);
    }
}
