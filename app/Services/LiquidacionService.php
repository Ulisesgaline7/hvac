<?php

namespace App\Services;

use App\Models\LiquidacionTecnico;
use App\Models\PagoTecnico;
use App\Models\Tecnico;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

/** Liquidación semanal de técnicos: generar, pagar (con número RT-AAAA-000001) y anular. */
class LiquidacionService
{
    public const BORRADOR = 'borrador';
    public const PAGADA = 'pagada';
    public const ANULADA = 'anulada';

    private const PAGO_APROBADO = 'aprobado';
    private const PAGO_LIQUIDADO = 'liquidado';

    /**
     * Agrupa por técnico los pagos aprobados hasta el fin de la semana que contiene $fecha.
     * No repite técnico+semana si ya hay una liquidación no anulada.
     *
     * @return array{creadas: int, omitidas: int}
     */
    public function generarSemana(?string $fecha = null): array
    {
        $ini = Carbon::parse($fecha ?? now())->startOfWeek(Carbon::MONDAY)->startOfDay();
        $fin = $ini->copy()->addDays(6)->endOfDay();

        $porTecnico = PagoTecnico::query()
            ->where('estado', self::PAGO_APROBADO)
            ->whereNull('liquidacion_id')
            ->where('monto', '>', 0)
            ->where('aprobado_en', '<=', $fin)
            ->get()
            ->groupBy('tecnico_id');

        $creadas = 0;
        $omitidas = 0;

        foreach ($porTecnico as $tecnicoId => $pagos) {
            $existe = LiquidacionTecnico::where('tecnico_id', $tecnicoId)
                ->whereDate('semana_inicio', $ini->toDateString())
                ->where('estado', '!=', self::ANULADA)
                ->exists();
            if ($existe) {
                $omitidas++;
                continue;
            }

            DB::transaction(function () use ($tecnicoId, $pagos, $ini, $fin, &$creadas) {
                $tecnico = Tecnico::withTrashed()->find($tecnicoId);
                $pct = $tecnico?->retencion_pct !== null ? (float) $tecnico->retencion_pct : null;
                $bruto = round((float) $pagos->sum('monto'), 2);
                $retencion = $pct ? round($bruto * $pct / 100, 2) : 0.0;

                $liq = LiquidacionTecnico::create([
                    'tecnico_id' => $tecnicoId,
                    'semana_inicio' => $ini->toDateString(),
                    'semana_fin' => $fin->toDateString(),
                    'bruto' => $bruto,
                    'retencion_pct' => $pct,
                    'retencion' => $retencion,
                    'neto' => round($bruto - $retencion, 2),
                    'estado' => self::BORRADOR,
                ]);

                PagoTecnico::whereIn('id', $pagos->pluck('id'))->update(['liquidacion_id' => $liq->id]);
                $creadas++;
            });
        }

        return ['creadas' => $creadas, 'omitidas' => $omitidas];
    }

    /** Marca como pagada, asigna el número y pasa los pagos a «liquidado». */
    public function pagar(LiquidacionTecnico $liq, string $metodo, ?string $referencia, string $recibe, int $usuarioId): LiquidacionTecnico
    {
        return DB::transaction(function () use ($liq, $metodo, $referencia, $recibe, $usuarioId) {
            $liq = LiquidacionTecnico::whereKey($liq->id)->lockForUpdate()->firstOrFail();

            if ($liq->estado !== self::BORRADOR) {
                throw new DomainException("Solo se paga una liquidación en borrador (actual: «{$liq->estado}»).");
            }
            if (! $liq->pagos()->exists()) {
                throw new DomainException('La liquidación no tiene pagos.');
            }

            $liq->update([
                'numero' => $this->siguienteNumero(),
                'estado' => self::PAGADA,
                'metodo_pago' => $metodo,
                'referencia_pago' => $referencia,
                'recibe_nombre' => $recibe,
                'pagada_por' => $usuarioId,
                'pagada_en' => now(),
            ]);

            $liq->pagos()->update(['estado' => self::PAGO_LIQUIDADO]);

            return $liq->fresh();
        });
    }

    /** Anula y libera los pagos para volver a liquidarlos. El número emitido no se reutiliza. */
    public function anular(LiquidacionTecnico $liq, string $motivo): LiquidacionTecnico
    {
        return DB::transaction(function () use ($liq, $motivo) {
            $liq = LiquidacionTecnico::whereKey($liq->id)->lockForUpdate()->firstOrFail();

            if ($liq->estado === self::ANULADA) {
                throw new DomainException('La liquidación ya está anulada.');
            }

            $liq->pagos()->update(['estado' => self::PAGO_APROBADO, 'liquidacion_id' => null]);
            $liq->update([
                'estado' => self::ANULADA,
                'anulada_en' => now(),
                'motivo_anulacion' => $motivo,
            ]);

            return $liq->fresh();
        });
    }

    /** Consecutivo anual. El candado de asesor evita que dos pagos simultáneos tomen el mismo número. */
    private function siguienteNumero(): string
    {
        $anio = now()->format('Y');
        DB::select('select pg_advisory_xact_lock(?)', [(int) ('27' . $anio)]);

        $ultimo = LiquidacionTecnico::where('numero', 'like', "RT-{$anio}-%")->max('numero');
        $sig = $ultimo ? ((int) substr($ultimo, -6)) + 1 : 1;

        return sprintf('RT-%s-%06d', $anio, $sig);
    }
}
