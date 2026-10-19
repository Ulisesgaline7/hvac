<?php

namespace App\Models;

use App\Enums\EstadoEquipo;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\AislaPorCliente;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Models\Concerns\RegistraCreador;
use App\Services\GeneradorCodigoEquipo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Equipo extends Model
{
    use AislaPorCliente, Auditable, RegistraCreador, SoftDeletes;

    protected $table = 'equipos';
    protected $guarded = [];

    protected $casts = [
        'estado' => EstadoEquipo::class,
        'datos_placa' => 'array',
        'fecha_instalacion' => 'date',
        'garantia_hasta' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (Equipo $equipo) {
            if (empty($equipo->token_qr)) {
                $equipo->token_qr = Str::random(32);
            }
            if (empty($equipo->codigo)) {
                $equipo->codigo = app(GeneradorCodigoEquipo::class)->siguiente();
            }
        });
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoEquipo::class, 'tipo_equipo_id');
    }

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class);
    }

    public function refrigerante(): BelongsTo
    {
        return $this->belongsTo(Refrigerante::class);
    }

    protected static function restringirACliente(Builder $consulta, int $clienteId): void
    {
        $consulta->whereIn('equipos.area_id', DB::table('areas')
            ->join('sucursales', 'sucursales.id', '=', 'areas.sucursal_id')
            ->where('sucursales.cliente_id', $clienteId)
            ->select('areas.id'));
    }
}
