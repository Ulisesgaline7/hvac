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
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Equipo extends Model implements HasMedia
{
    use AislaPorCliente, Auditable, InteractsWithMedia, RegistraCreador, SoftDeletes;

    protected $table = 'equipos';
    protected $guarded = [];

    protected $casts = [
        'estado' => EstadoEquipo::class,
        'datos_placa' => 'array',
        'fecha_instalacion' => 'date',
        'garantia_hasta' => 'date',
    ];

    /** Documentos escaneados del equipo (manual, ficha del fabricante, garantía). Disco privado. */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documentos_equipo')->useDisk('local');
    }

    protected static function booted(): void
    {
        static::saving(function (Equipo $equipo) {
            $d = $equipo->datos_placa ?? [];
            if (isset($d['capacidad']) && $d['capacidad'] !== '') {
                $equipo->capacidad_valor = $d['capacidad'];
                $equipo->capacidad_unidad = $d['capacidad_unidad'] ?? $equipo->capacidad_unidad;
            }
        });

        static::creating(function (Equipo $equipo) {
            if (empty($equipo->token_qr)) {
                $equipo->token_qr = Str::random(32);
            }
            if (empty($equipo->codigo)) {
                $equipo->codigo = app(GeneradorCodigoEquipo::class)->siguiente();
            }
        });
    }

    public function lineas(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OrdenTrabajoEquipo::class, 'equipo_id');
    }

    public function documentosEquipo(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->media()->where('collection_name', 'documentos_equipo');
    }

    public function garantias(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Garantia::class, 'equipo_id');
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

    /** Capacidad lista para mostrar, p. ej. "18000 BTU" (sin ceros sobrantes). */
    public function getCapacidadTextoAttribute(): ?string
    {
        if (! $this->capacidad_valor) {
            return null;
        }

        $valor = (string) $this->capacidad_valor;
        if (str_contains($valor, '.')) {
            $valor = rtrim(rtrim($valor, '0'), '.');
        }

        return trim($valor . ' ' . $this->capacidad_unidad);
    }

    protected static function restringirACliente(Builder $consulta, int $clienteId): void
    {
        $consulta->whereIn('equipos.area_id', DB::table('areas')
            ->join('sucursales', 'sucursales.id', '=', 'areas.sucursal_id')
            ->where('sucursales.cliente_id', $clienteId)
            ->select('areas.id'));
    }
}
