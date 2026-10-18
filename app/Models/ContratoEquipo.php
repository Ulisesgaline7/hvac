<?php

namespace App\Models;

use App\Enums\Frecuencia;
use App\Models\Concerns\AislaPorCliente;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\RegistraCreador;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class ContratoEquipo extends Model
{
    use AislaPorCliente, Auditable, RegistraCreador;

    protected $table = 'contrato_equipo';
    protected $guarded = [];

    protected $casts = [
        'frecuencia' => Frecuencia::class,
        'fecha_inicio' => 'date',
        'proxima_fecha' => 'date',
    ];

    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class);
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    protected static function restringirACliente(Builder $consulta, int $clienteId): void
    {
        $consulta->whereIn('contrato_id', DB::table('contratos')->where('cliente_id', $clienteId)->select('id'));
    }
}
