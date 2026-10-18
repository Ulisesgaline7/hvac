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

class Area extends Model
{
    use AislaPorCliente, Auditable, RegistraCreador, SoftDeletes;

    protected $table = 'areas';
    protected $guarded = [];

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class);
    }

    protected static function restringirACliente(Builder $consulta, int $clienteId): void
    {
        $consulta->whereIn('sucursal_id', DB::table('sucursales')->where('cliente_id', $clienteId)->select('id'));
    }
}
