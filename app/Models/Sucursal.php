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

    /** Contactos propios de la sucursal; si no tiene, usa los del cliente. */
    public function contactosEfectivos(): array
    {
        $propios = array_values(array_filter($this->contactos ?? []));

        return $propios ?: array_values($this->cliente?->contactos ?? []);
    }

    /** URL de Google Maps: por coordenadas si existen, si no por dirección. */
    public static function mapaUrl(?string $direccion, $latitud, $longitud): ?string
    {
        if (filled($latitud) && filled($longitud)) {
            $q = $latitud . ',' . $longitud;
        } elseif (filled($direccion)) {
            $q = $direccion;
        } else {
            return null;
        }

        return 'https://www.google.com/maps/search/?api=1&query=' . urlencode($q);
    }

    public function urlMapa(): ?string
    {
        return self::mapaUrl($this->direccion, $this->latitud, $this->longitud);
    }

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
