<?php

namespace App\Services;

use App\Enums\EstadoOt;
use App\Enums\TipoContrato;
use App\Enums\TipoOt;
use App\Models\Contrato;
use App\Models\Cotizacion;
use App\Models\OrdenTrabajo;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Borrador → enviada → aprobada o rechazada. Al aprobar crea lo que corresponda. */
class CotizacionFlujo
{
    /** Categoría del tarifario → tipo de OT. Repuestos y gas no generan OT. */
    private const TIPO_OT = [
        'preventivo' => 'preventivo',
        'refrigeracion_comercial' => 'preventivo',
        'instalacion' => 'instalacion',
        'correctivo' => 'correctivo',
        'administracion' => 'correctivo',
    ];

    public function enviar(Cotizacion $c): Cotizacion
    {
        $this->exigirEstado($c, 'borrador', 'enviar');

        if (! $c->lineas()->exists()) {
            throw new DomainException('Agregue al menos una línea antes de enviar la cotización.');
        }
        if ($c->lineas()->where('precio_unitario', '<=', 0)->exists()) {
            throw new DomainException('Hay líneas sin precio. Defina el precio de las que dicen «Cotizar».');
        }

        $c = app(CotizacionService::class)->recalcular($c);
        $c->update([
            'estado' => 'enviada',
            'enviada_en' => now(),
            'token_aceptacion' => $c->token_aceptacion ?? Str::random(40),
            'valida_hasta' => $c->valida_hasta ?? now()->addWeekdays((int) config('tarifario.validez_dias_habiles')),
        ]);

        return $c->fresh();
    }

    /** @return array{contrato: ?Contrato, ordenes: Collection<int, OrdenTrabajo>} */
    public function aprobar(Cotizacion $c, string $aprobadaPor, ?string $fechaProgramada = null): array
    {
        $this->exigirEstado($c, 'enviada', 'aprobar');

        if (trim($aprobadaPor) === '') {
            throw new DomainException('Indique quién aprueba por parte del cliente.');
        }
        if (! $c->sucursal_id) {
            throw new DomainException('Indique la sucursal antes de aprobar la cotización.');
        }

        return DB::transaction(function () use ($c, $aprobadaPor, $fechaProgramada) {
            $porTipo = $this->lineasPorTipoOt($c);
            $contrato = null;

            if ($c->descuento_contrato && $porTipo->has('preventivo')) {
                $contrato = $this->crearContrato($c, $porTipo->get('preventivo'));

                foreach ($c->equipos()->get() as $e) {
                    DB::table('contrato_equipo')->insert([
                        'contrato_id' => $contrato->id,
                        'equipo_id' => $e->id,
                        'fecha_inicio' => now()->toDateString(),
                        'creado_por' => \Illuminate\Support\Facades\Auth::id(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                $porTipo->forget('preventivo');
            }

            $equipos = $c->equipos()->get();

            $ordenes = $porTipo->map(function (Collection $lineas, string $tipo) use ($c, $fechaProgramada, $equipos) {
                $ot = OrdenTrabajo::create([
                    'tipo' => TipoOt::from($tipo),
                    'prioridad' => 'normal',
                    'estado' => EstadoOt::Pendiente,
                    'cliente_id' => $c->cliente_id,
                    'sucursal_id' => $c->sucursal_id,
                    'cotizacion_id' => $c->id,
                    'fecha_programada' => $fechaProgramada,
                    'descripcion' => $this->resumen($c, $lineas),
                ]);

                foreach ($equipos as $e) {
                    $ot->lineas()->create([
                        'equipo_id' => $e->id,
                        'tipo_equipo_id' => $e->tipo_equipo_id,
                        'area_id' => $e->area_id,
                    ]);
                }

                return $ot;
            })->values();

            $c->update([
                'estado' => 'aprobada',
                'aprobada_en' => now(),
                'aprobada_por_nombre' => trim($aprobadaPor),
                'contrato_id' => $contrato?->id,
            ]);

            return ['contrato' => $contrato, 'ordenes' => $ordenes];
        });
    }

    public function rechazar(Cotizacion $c, ?string $motivo = null): Cotizacion
    {
        $this->exigirEstado($c, 'enviada', 'rechazar');

        $nota = 'Rechazada el ' . now()->format('d/m/Y') . ($motivo ? ': ' . trim($motivo) : '.');
        \Illuminate\Support\Facades\DB::transaction(function () use ($c, $nota) {
            $c->update(['estado' => 'rechazada', 'notas' => trim(($c->notas ? $c->notas . "\n" : '') . $nota)]);
            app(\App\Services\FacturacionService::class)->facturarVisitaRechazada($c->fresh());
        });

        return $c->fresh();
    }

    private function lineasPorTipoOt(Cotizacion $c): Collection
    {
        return $c->lineas()->with('servicio')->get()
            ->filter(fn ($l) => $l->tipo === 'servicio' && $l->servicio && isset(self::TIPO_OT[$l->servicio->categoria]))
            ->groupBy(fn ($l) => self::TIPO_OT[$l->servicio->categoria]);
    }

    private function crearContrato(Cotizacion $c, Collection $lineas): Contrato
    {
        $inicio = now()->startOfDay();
        $nombre = config("tarifario.descuentos.{$c->descuento_contrato}.nombre", $c->descuento_contrato);

        return Contrato::create([
            'cliente_id' => $c->cliente_id,
            'tipo' => TipoContrato::from('preventivo_recurrente'),
            'vigencia_inicio' => $inicio->toDateString(),
            'vigencia_fin' => $inicio->copy()->addYear()->subDay()->toDateString(),
            'servicios_incluidos' => $this->resumen($c, $lineas),
            'notas' => "Generado desde la cotización {$c->numero} ({$nombre}). Equipos asociados desde la cotización. Falta definir la frecuencia de cada uno y activarlo.",
        ]);
    }

    private function resumen(Cotizacion $c, Collection $lineas): string
    {
        $detalle = $lineas->map(fn ($l) => rtrim(rtrim((string) $l->cantidad, '0'), '.') . ' × ' . $l->descripcion)->implode('; ');

        return Str::limit("Cotización {$c->numero}: {$detalle}", 900);
    }

    private function exigirEstado(Cotizacion $c, string $esperado, string $accion): void
    {
        if ($c->estado !== $esperado) {
            throw new DomainException("Solo se puede {$accion} una cotización en estado «{$esperado}» (actual: «{$c->estado}»).");
        }
    }
}
