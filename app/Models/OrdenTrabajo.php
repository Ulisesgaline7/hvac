<?php

namespace App\Models;

use App\Enums\EstadoOt;
use App\Enums\TipoOt;
use App\Models\Concerns\AislaPorCliente;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\RegistraCreador;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrdenTrabajo extends Model
{
    use AislaPorCliente, Auditable, RegistraCreador, SoftDeletes;

    protected $table = 'ordenes_trabajo';
    protected $guarded = [];

    protected $casts = [
        'tipo' => TipoOt::class,
        'estado' => EstadoOt::class,
        'fecha_programada' => 'date',
        'fecha_original' => 'date',
        'aceptada_en' => 'datetime',
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
        'cerrada_en' => 'datetime',
        'tardia' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (OrdenTrabajo $ot) {
            if (empty($ot->numero)) {
                $ot->numero = 'TMP-' . Str::uuid();
            }
            if (empty($ot->estado)) {
                $ot->estado = EstadoOt::Pendiente;
            }
        });

        static::created(function (OrdenTrabajo $ot) {
            if (str_starts_with($ot->numero, 'TMP-')) {
                $numero = sprintf('OT-%s-%06d', now()->format('Y'), $ot->id);
                DB::table('ordenes_trabajo')->where('id', $ot->id)->update(['numero' => $numero]);
                $ot->numero = $numero;
                $ot->syncOriginalAttribute('numero');
            }

            self::registrarEstado($ot, null, $ot->estado);
        });

        static::updated(function (OrdenTrabajo $ot) {
            if ($ot->wasChanged('estado')) {
                self::registrarEstado($ot, $ot->getOriginal('estado'), $ot->estado);
            }
        });
    }

    private static function registrarEstado(OrdenTrabajo $ot, mixed $anterior, mixed $nuevo): void
    {
        $valor = fn ($e) => $e instanceof BackedEnum ? $e->value : $e;

        DB::table('historial_estados_ot')->insert([
            'orden_trabajo_id' => $ot->id,
            'estado_anterior' => $valor($anterior),
            'estado_nuevo' => $valor($nuevo),
            'usuario_id' => Auth::id(),
            'motivo' => $ot->motivoCambio,
            'created_at' => now(),
        ]);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class);
    }

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(Tecnico::class);
    }

    public function origen(): BelongsTo
    {
        return $this->belongsTo(self::class, 'ot_origen_id');
    }

    public function equipos(): BelongsToMany
    {
        return $this->belongsToMany(Equipo::class, 'orden_trabajo_equipo')
            ->withPivot(['tipo_equipo_id', 'area_id', 'datos_previstos', 'estado_resultante', 'observaciones'])
            ->withTimestamps();
    }

    protected static function restringirACliente(Builder $consulta, int $clienteId): void
    {
        $consulta->where('cliente_id', $clienteId);
    }

    public ?string $motivoCambio = null;

    public function historial(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(HistorialEstadoOt::class, 'orden_trabajo_id')->latest('id');
    }

    public function cambiarEstado(EstadoOt $nuevo, ?string $motivo = null): void
    {
        if (! in_array($nuevo, $this->estado->transiciones(), true)) {
            throw new \DomainException("No se puede pasar de «{$this->estado->getLabel()}» a «{$nuevo->getLabel()}».");
        }

        if ($this->estado === EstadoOt::EnRevision && $nuevo === EstadoOt::EnProceso) {
            $this->motivo_devolucion = $motivo;
        }

        if ($nuevo === EstadoOt::Cerrada) {
            $this->cerrada_en = now();
            $this->tardia = $this->fecha_programada ? now()->startOfDay()->gt($this->fecha_programada) : false;
        }

        $this->motivoCambio = $motivo;
        $this->estado = $nuevo;
        $this->save();
        $this->motivoCambio = null;
    }
}
