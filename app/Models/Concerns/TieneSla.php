<?php

namespace App\Models\Concerns;

use App\Enums\EstadoOt;
use App\Enums\TipoOt;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * SLA, atrasos y scopes de lista para OrdenTrabajo.
 * No requiere columnas nuevas: usa created_at, check_in_at, cerrada_en y fecha_programada.
 */
trait TieneSla
{
    public const PRIORIDADES = [
        'baja' => 'Baja',
        'normal' => 'Normal',
        'alta' => 'Urgente',
        'emergencia' => 'Emergencia',
    ];

    /** Tipos que se miden por SLA. Los demás se miden contra su fecha programada. */
    public static function tiposConSla(): array
    {
        return [TipoOt::Correctivo, TipoOt::Emergencia, TipoOt::VisitaTecnica];
    }

    public function tieneSla(): bool
    {
        return in_array($this->tipo, static::tiposConSla(), true);
    }

    public function esAbierta(): bool
    {
        return ! in_array($this->estado, [EstadoOt::Cerrada, EstadoOt::Cancelada], true);
    }

    // ───────────── Scopes ─────────────

    public function scopeAbiertas(Builder $q): Builder
    {
        return $q->whereNotIn('estado', [EstadoOt::Cerrada->value, EstadoOt::Cancelada->value]);
    }

    public function scopeSinAsignar(Builder $q): Builder
    {
        return $q->abiertas()->whereNull('tecnico_id');
    }

    /** SLA vencido (reactivas) o fecha programada pasada (preventivas e instalaciones). */
    public function scopeAtrasadas(Builder $q): Builder
    {
        $reactivos = array_map(fn (TipoOt $t) => $t->value, static::tiposConSla());
        $esperando = [EstadoOt::Pendiente->value, EstadoOt::Asignada->value];

        return $q->abiertas()->where(function (Builder $q) use ($reactivos, $esperando) {
            foreach (config('sla.prioridades') as $prioridad => $h) {
                $q->orWhere(function (Builder $q) use ($prioridad, $h, $reactivos, $esperando) {
                    $q->whereIn('tipo', $reactivos)
                        ->where('prioridad', $prioridad)
                        ->where(function (Builder $q) use ($h, $esperando) {
                            // Aún sin atender: corre el plazo de atención.
                            $q->where(fn (Builder $q) => $q->whereNull('check_in_at')
                                ->whereIn('estado', $esperando)
                                ->where('created_at', '<', now()->subHours($h['atencion'])))
                            // Ya atendida: corre el plazo de cierre.
                              ->orWhere(fn (Builder $q) => $q
                                ->where(fn (Builder $q) => $q->whereNotNull('check_in_at')->orWhereNotIn('estado', $esperando))
                                ->where('created_at', '<', now()->subHours($h['cierre'])));
                        });
                });
            }

            $q->orWhere(fn (Builder $q) => $q->whereNotIn('tipo', $reactivos)
                ->whereNotNull('fecha_programada')
                ->whereDate('fecha_programada', '<', today()));
        });
    }

    // ───────────── SLA ─────────────

    public static function slaConfig(?string $prioridad): array
    {
        return config('sla.prioridades.' . ($prioridad ?: 'normal')) ?? config('sla.prioridades.normal');
    }

    private function esperandoAtencion(): bool
    {
        return $this->check_in_at === null
            && in_array($this->estado, [EstadoOt::Pendiente, EstadoOt::Asignada], true);
    }

    /** Próximo vencimiento de SLA (atención mientras no llega el técnico, luego cierre). */
    public function slaVence(): ?Carbon
    {
        if (! $this->tieneSla() || ! $this->esAbierta() || ! $this->created_at) {
            return null;
        }

        $sla = static::slaConfig($this->prioridad);

        return $this->created_at->copy()->addHours($this->esperandoAtencion() ? $sla['atencion'] : $sla['cierre']);
    }

    private static function lapso(CarbonInterface $fecha): string
    {
        return $fecha->diffForHumans(['parts' => 1, 'short' => true, 'syntax' => CarbonInterface::DIFF_ABSOLUTE]);
    }

    /**
     * @return array{0:string,1:string,2:string} [clave, etiqueta, color de Filament]
     */
    public function estadoSla(): array
    {
        if ($this->estado === EstadoOt::Cancelada) {
            return ['na', '—', 'gray'];
        }

        if ($this->estado === EstadoOt::Cerrada) {
            if ($this->tieneSla() && $this->created_at && $this->cerrada_en) {
                $limite = $this->created_at->copy()->addHours(static::slaConfig($this->prioridad)['cierre']);

                return $this->cerrada_en->lte($limite)
                    ? ['cumplida', 'Cumplido', 'success']
                    : ['incumplida', 'Fuera de plazo', 'danger'];
            }

            return $this->tardia ? ['incumplida', 'Tardía', 'danger'] : ['cumplida', 'A tiempo', 'success'];
        }

        // Preventivas e instalaciones: contra la fecha programada.
        if (! $this->tieneSla()) {
            if (! $this->fecha_programada) {
                return ['en_plazo', 'Sin programar', 'gray'];
            }

            return $this->fecha_programada->lt(today())
                ? ['vencida', 'Atrasada ' . static::lapso($this->fecha_programada), 'danger']
                : ['en_plazo', 'En fecha', 'gray'];
        }

        $vence = $this->slaVence();

        if (! $vence) {
            return ['na', '—', 'gray'];
        }

        if (now()->gt($vence)) {
            return ['vencida', 'Vencida hace ' . static::lapso($vence), 'danger'];
        }

        $restan = max(0, (int) now()->diffInMinutes($vence, false));
        $total = abs((int) $this->created_at->diffInMinutes($vence, false));

        if ($restan <= max(240, (int) ($total * 0.25))) {
            return ['por_vencer', 'Vence en ' . static::lapso($vence), 'warning'];
        }

        return ['en_plazo', 'Vence en ' . static::lapso($vence), 'success'];
    }
}
