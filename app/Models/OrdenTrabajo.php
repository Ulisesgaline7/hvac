<?php

namespace App\Models;

use App\Models\Concerns\PerteneceADivision;
use App\Enums\EstadoOt;
use App\Enums\TipoOt;
use App\Models\Concerns\AislaPorCliente;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\RegistraCreador;
use App\Models\Concerns\TieneSla;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrdenTrabajo extends Model implements \Spatie\MediaLibrary\HasMedia
{
    use PerteneceADivision;

    use AislaPorCliente, Auditable, RegistraCreador, SoftDeletes, TieneSla;
    use \Spatie\MediaLibrary\InteractsWithMedia;

    /** Documentos escaneados de la OT (acta firmada, orden de servicio, etc.). Disco privado. */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documentos_ot')->useDisk('local');
    }

    protected $table = 'ordenes_trabajo';
    protected $guarded = [];

    protected $casts = [
        'tipo' => TipoOt::class,
        'estado' => EstadoOt::class,
        'fecha_programada' => 'date',
        'fecha_original' => 'date',
        'seguridad_previa' => 'array',
        'precio_estimado' => 'decimal:2',
        'anticipo' => 'decimal:2',
        'aceptada_en' => 'datetime',
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
        'cerrada_en' => 'datetime',
        'tardia' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::updating(function (OrdenTrabajo $ot) {
            $original = $ot->getOriginal('estado');
            $original = $original instanceof EstadoOt ? $original : EstadoOt::tryFrom((string) $original);

            // Cerrada y cancelada son de solo lectura.
            if (in_array($original, [EstadoOt::Cerrada, EstadoOt::Cancelada], true) && $ot->isDirty()) {
                throw new \DomainException('La OT está ' . strtolower($original->getLabel()) . ' y no se puede modificar.');
            }
        });

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

        static::saved(function (OrdenTrabajo $ot) {
            // El tecnico_id es el líder: la cuadrilla lo refleja siempre con rol «lider».
            if (! $ot->tecnico_id || ! ($ot->wasRecentlyCreated || $ot->wasChanged('tecnico_id'))) {
                return;
            }

            DB::table('orden_trabajo_tecnico')
                ->where('orden_trabajo_id', $ot->id)->where('rol', 'lider')->where('tecnico_id', '!=', $ot->tecnico_id)
                ->delete();

            DB::table('orden_trabajo_tecnico')->updateOrInsert(
                ['orden_trabajo_id' => $ot->id, 'tecnico_id' => $ot->tecnico_id],
                ['rol' => 'lider', 'created_at' => now(), 'updated_at' => now()],
            );
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

    public function lineas(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OrdenTrabajoEquipo::class, 'orden_trabajo_id');
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

    public function actas(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ActaConformidad::class, 'orden_trabajo_id');
    }

    public function trabajosAdicionales(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TrabajoAdicional::class, 'orden_trabajo_id');
    }

    public ?string $motivoCambio = null;

    /** Evita repetir la elevación de contexto cuando cambiarEstado se vuelve a llamar a sí mismo. */
    private bool $elevado = false;

    public function historial(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(HistorialEstadoOt::class, 'orden_trabajo_id')->latest('id');
    }

    /** Guardas de negocio: no se completa ni se cierra una OT con fichas pendientes. */
    private function validarTransicion(EstadoOt $nuevo, ?string $motivo): void
    {
        if (! in_array($nuevo, [EstadoOt::CompletadaTecnico, EstadoOt::Cerrada], true)) {
            return;
        }

        if ($this->tipo === TipoOt::VisitaTecnica) {
            if (blank($this->diagnostico)) {
                throw new \DomainException('Registre el diagnóstico de la visita antes de completarla.');
            }

            return;
        }

        $lineas = $this->lineas()->with('ficha')->get();

        if ($lineas->isEmpty()) {
            throw new \DomainException('La OT no tiene equipos registrados.');
        }

        $incompletas = $lineas->filter(fn ($l) => ! $l->ficha?->completa)->count();
        if ($incompletas > 0) {
            throw new \DomainException("Hay {$incompletas} equipo(s) con la ficha técnica incompleta o sin llenar.");
        }

        if ($this->tipo === TipoOt::Instalacion) {
            $sinCarga = $lineas->filter(function ($l) {
                $r = $l->ficha?->respuestas ?? [];
                $legado = filled($r['refrigerante_cantidad'] ?? null);
                $noAplica = ($r['carga_registrada'] ?? null) === 'N/A';
                $hayCarga = \App\Models\MovimientoRefrigerante::where('orden_trabajo_equipo_id', $l->id)
                    ->where('tipo', 'carga')->exists();

                return ! ($legado || $noAplica || $hayCarga);
            })->count();

            if ($sinCarga > 0) {
                throw new \DomainException("Hay {$sinCarga} equipo(s) sin carga de refrigerante registrada. Regístrala en la línea o marca «Carga de refrigerante registrada» como N/A en la ficha.");
            }
        }

        $sinResponder = $lineas->sum(fn ($l) => \App\Support\ChecklistLinea::pendientes($l));
        if ($sinResponder > 0) {
            throw new \DomainException("Quedan {$sinResponder} actividad(es) del checklist sin responder.");
        }

        if ($nuevo === EstadoOt::Cerrada
            && $lineas->contains(fn ($l) => \App\Support\ChecklistLinea::fallas($l) !== [])
            && blank($motivo)) {
            throw new \DomainException('Hay actividades del checklist con falla. Indica en el motivo cómo se atendieron antes de cerrar.');
        }

        if (in_array($this->tipo, [TipoOt::Instalacion, TipoOt::Emergencia], true)
            && ! $this->actas()->get()->contains(fn ($a) => $a->getMedia('firma_cliente')->isNotEmpty())) {
            throw new \DomainException('Una OT de instalación o emergencia necesita el acta de conformidad firmada por el cliente para completarse o cerrarse. Regístrala en la pestaña «Acta de conformidad».');
        }

        if ($nuevo === EstadoOt::Cerrada
            && $lineas->contains(fn ($l) => ! empty($l->ficha?->alertas))
            && blank($motivo)) {
            throw new \DomainException('Hay valores fuera de rango. Indica en el motivo cómo se atendieron antes de cerrar.');
        }
    }

    /** Cuadrilla asignada: el rol (lider, tecnico, ayudante, supervisor) va en el pivote. */
    public function cuadrilla(): BelongsToMany
    {
        return $this->belongsToMany(Tecnico::class, 'orden_trabajo_tecnico', 'orden_trabajo_id', 'tecnico_id')
            ->withPivot('rol')
            ->withTimestamps();
    }

    /**
     * Supervisor de la OT: usuario con rol «supervisor» que figura en su cuadrilla solo como supervisor
     * (ni líder ni ejecutor). La cuadrilla se lee con contexto interno para no depender de lo que el RLS le deje ver.
     */
    public function esSupervisorDe($usuario): bool
    {
        if (! $this->exists || ! $usuario || ! method_exists($usuario, 'hasRole') || ! $usuario->hasRole('supervisor')) {
            return false;
        }

        return \App\Providers\RlsServiceProvider::comoInternoConUsuario(function () use ($usuario) {
            $roles = DB::table('orden_trabajo_tecnico as ott')
                ->join('tecnicos as t', 't.id', '=', 'ott.tecnico_id')
                ->where('ott.orden_trabajo_id', $this->id)
                ->where('t.user_id', $usuario->getKey())
                ->pluck('ott.rol');

            $esLiderDeLaOt = $this->tecnico_id
                && DB::table('tecnicos')->where('id', $this->tecnico_id)->where('user_id', $usuario->getKey())->exists();

            return $roles->contains('supervisor')
                && ! $roles->contains(fn ($r) => $r !== 'supervisor')
                && ! $esLiderDeLaOt;
        });
    }

    public function cambiarEstado(EstadoOt $nuevo, ?string $motivo = null): void
    {
        if (! in_array($nuevo, $this->estado->transiciones(), true)) {
            throw new \DomainException("No se puede pasar de «{$this->estado->getLabel()}» a «{$nuevo->getLabel()}».");
        }

        $usuario = Auth::user();
        $esCierreODevolucion = $nuevo === EstadoOt::Cerrada
            || ($this->estado === EstadoOt::EnRevision && $nuevo === EstadoOt::EnProceso);

        if ($esCierreODevolucion && $usuario
            && ! $usuario->hasAnyRole(['administrador', 'coordinador'])
            && ! $this->esSupervisorDe($usuario)) {
            throw new \DomainException('Solo coordinador, administrador o el supervisor asignado pueden cerrar o devolver la OT.');
        }

        // El supervisor asignado revisa, cierra y devuelve. En base de datos tiene permisos de técnico, así que la
        // operación corre con contexto interno (conservando su usuario). La autorización ya se validó arriba.
        $esPasoDeSupervisor = $esCierreODevolucion
            || ($this->estado === EstadoOt::CompletadaTecnico && $nuevo === EstadoOt::EnRevision);

        if ($esPasoDeSupervisor && ! $this->elevado && $usuario
            && ! $usuario->hasAnyRole(['administrador', 'coordinador'])
            && $this->esSupervisorDe($usuario)) {
            $this->elevado = true;
            try {
                \App\Providers\RlsServiceProvider::comoInternoConUsuario(fn () => $this->cambiarEstado($nuevo, $motivo));
            } finally {
                $this->elevado = false;
            }

            return;
        }

        if ($this->estado === EstadoOt::Asignada && $nuevo === EstadoOt::EnProceso) {
            app(\App\Services\AcuerdoTecnico::class)->exigirParaUsuario($usuario);
        }

        $this->validarTransicion($nuevo, $motivo);

        if ($this->estado === EstadoOt::Asignada && $nuevo === EstadoOt::EnProceso) {
            $this->check_in_at ??= now();
        }

        if ($nuevo === EstadoOt::CompletadaTecnico) {
            $this->check_out_at ??= now();
        }

        if ($this->estado === EstadoOt::EnRevision && $nuevo === EstadoOt::EnProceso) {
            $this->motivo_devolucion = $motivo;
        }

        if ($nuevo === EstadoOt::Cerrada) {
            $this->cerrada_en = now();
            $this->tardia = $this->fecha_programada ? now()->startOfDay()->gt($this->fecha_programada) : false;
        }

        DB::transaction(function () use ($nuevo, $motivo) {
            $this->motivoCambio = $motivo;
            $this->estado = $nuevo;
            $this->save();
            $this->motivoCambio = null;

            if ($nuevo === EstadoOt::Cerrada) {
                app(\App\Services\GarantiaService::class)->emitir($this);
                app(\App\Services\FacturacionService::class)->generarDesdeOt($this);
                app(\App\Services\PagoTecnicoService::class)->generar($this);
            }
        });
    }
}
