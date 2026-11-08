<?php

namespace App\Services;

use App\Enums\TipoOt;
use App\Models\CotizacionLinea;
use App\Models\OrdenTrabajo;
use App\Models\PagoTecnico;
use App\Models\TarifaTecnico;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/** Genera las líneas de pago a técnicos al cerrar una OT. El supervisor recibe PCT_SUPERVISOR %; del resto, líder 60 % y cuadrilla 40 %. */
class PagoTecnicoService
{
    private const PCT_LIDER = 60;

    /** % del monto base para los supervisores asignados (en partes iguales). El resto se reparte líder/cuadrilla. */
    private const PCT_SUPERVISOR = 10;

    /** Mismo mapa que CotizacionFlujo: categoría del tarifario → tipo de OT. */
    private const TIPO_OT = [
        'preventivo' => 'preventivo',
        'refrigeracion_comercial' => 'preventivo',
        'instalacion' => 'instalacion',
        'correctivo' => 'correctivo',
        'administracion' => 'correctivo',
    ];

    /** tipos_equipo.id → código del tarifario, para los tipos que no dependen de la capacidad. */
    private const CODIGO_POR_TIPO = [
        3 => 'T-07',   // Paquete / rooftop
        10 => 'T-08',  // Manejadora (UMA)
        4 => 'T-09',   // Chiller
        11 => 'T-18',  // Refrigerador
        6 => 'T-18',   // Congelador
        8 => 'T-18',   // Vitrina
        7 => 'T-18',   // Mesa fría
        9 => 'T-19',   // Máquina de hielo
        5 => 'T-20',   // Cuarto frío
        12 => 'T-21',  // Cámara de congelación
        13 => 'T-36',  // Estufa / cocina industrial
        14 => 'T-37',  // Plancha industrial
        15 => 'T-38',  // Horno
        16 => 'T-39',  // Freidora
        17 => 'T-40',  // Campana de extracción
    ];

    /** tipos_equipo.id que se tarifan por capacidad (T-01 a T-06): mini split y piso-techo. */
    private const TIPOS_POR_CAPACIDAD = [1, 2];

    /** Devuelve cuántas líneas creó. Es idempotente: si la OT ya tiene pagos, no hace nada. */
    public function generar(OrdenTrabajo $ot): int
    {
        if (PagoTecnico::where('orden_trabajo_id', $ot->id)->exists()) {
            return 0;
        }

        $tipo = $ot->tipo instanceof TipoOt ? $ot->tipo : TipoOt::from((string) $ot->tipo);
        $retorno = $tipo === TipoOt::Correctivo && $ot->ot_origen_id;

        $miembros = $this->miembros($ot);
        if ($miembros['lider'] === null) {
            Log::warning("OT {$ot->numero} cerrada sin técnicos asignados: no se generaron pagos.");

            return 0;
        }

        $conceptos = $retorno
            ? [['codigo' => null, 'concepto' => 'Retorno por falla de ejecución (sin pago, revisar)', 'base' => 0.0]]
            : $this->conceptos($ot, $tipo);

        $n = 0;
        foreach ($conceptos as $c) {
            $estado = $retorno ? 'retorno' : ($c['base'] > 0 ? 'pendiente' : 'sin_tarifa');
            foreach ($this->reparto($c['base'], $miembros) as $r) {
                PagoTecnico::create([
                    'orden_trabajo_id' => $ot->id,
                    'tecnico_id' => $r['tecnico_id'],
                    'rol' => $r['rol'],
                    'codigo_servicio' => $c['codigo'],
                    'concepto' => $c['concepto'],
                    'monto_base' => $c['base'],
                    'porcentaje' => $r['pct'],
                    'monto' => $r['monto'],
                    'estado' => $estado,
                ]);
                $n++;
            }
        }

        return $n;
    }

    /** @return array{lider: ?int, auxiliares: array<int, array{id: int, rol: string}>, supervisores: array<int, int>} */
    private function miembros(OrdenTrabajo $ot): array
    {
        $todas = DB::table('orden_trabajo_tecnico')->where('orden_trabajo_id', $ot->id)->get();
        $supervisores = $todas->where('rol', 'supervisor')->pluck('tecnico_id')->map(fn ($i) => (int) $i)->unique()->values()->all();
        $filas = $todas->filter(fn ($f) => $f->rol !== 'supervisor')->values();

        if ($filas->isEmpty() && $ot->tecnico_id) {
            return ['lider' => (int) $ot->tecnico_id, 'auxiliares' => [], 'supervisores' => $supervisores];
        }

        $lider = $filas->firstWhere('rol', 'lider') ?? $filas->first();
        if (! $lider) {
            return ['lider' => null, 'auxiliares' => [], 'supervisores' => $supervisores];
        }

        return [
            'lider' => (int) $lider->tecnico_id,
            'auxiliares' => $filas->filter(fn ($f) => (int) $f->tecnico_id !== (int) $lider->tecnico_id)
                ->map(fn ($f) => ['id' => (int) $f->tecnico_id, 'rol' => (string) $f->rol])->values()->all(),
            'supervisores' => $supervisores,
        ];
    }

    /** @return array<int, array{codigo: ?string, concepto: string, base: float}> */
    private function conceptos(OrdenTrabajo $ot, TipoOt $tipo): array
    {
        $tarifas = TarifaTecnico::pluck('monto', 'codigo');

        if ($tipo === TipoOt::VisitaTecnica) {
            return [['codigo' => 'T-22', 'concepto' => 'Visita técnica y diagnóstico', 'base' => (float) $tarifas->get('T-22', 0)]];
        }

        $lineas = $ot->cotizacion_id
            ? CotizacionLinea::with('servicio')->where('cotizacion_id', $ot->cotizacion_id)->where('tipo', 'servicio')->get()
                ->filter(fn ($l) => $l->servicio && (self::TIPO_OT[$l->servicio->categoria] ?? null) === $tipo->value)
            : collect();

        if ($lineas->isEmpty()) {
            // Preventiva de contrato: no hay cotización, el pago sale de los equipos de la OT.
            if ($tipo === TipoOt::Preventivo) {
                $porEquipo = $this->conceptosPorEquipo($ot, $tarifas);
                if ($porEquipo !== []) {
                    return $porEquipo;
                }
            }

            return [['codigo' => null, 'concepto' => 'OT sin cotización o sin servicios asociados: definir el monto', 'base' => 0.0]];
        }

        return $lineas->map(function ($l) use ($tarifas) {
            $cant = (float) $l->cantidad;

            return [
                'codigo' => $l->servicio->codigo,
                'concepto' => $l->servicio->nombre . ($cant != 1.0 ? ' × ' . rtrim(rtrim(number_format($cant, 2), '0'), '.') : ''),
                'base' => round((float) $tarifas->get($l->servicio->codigo, 0) * $cant, 2),
            ];
        })->values()->all();
    }

    /**
     * Un concepto por código de tarifario, contando los equipos de la OT.
     * Los equipos que no se pueden tarifar (sin tipo o sin capacidad) quedan en una línea aparte de base 0.
     *
     * @return array<int, array{codigo: ?string, concepto: string, base: float}>
     */
    private function conceptosPorEquipo(OrdenTrabajo $ot, $tarifas): array
    {
        $equipos = DB::table('orden_trabajo_equipo as ote')
            ->leftJoin('equipos as e', 'e.id', '=', 'ote.equipo_id')
            ->where('ote.orden_trabajo_id', $ot->id)
            ->get(['ote.tipo_equipo_id as tipo_linea', 'e.tipo_equipo_id', 'e.capacidad_valor', 'e.capacidad_unidad', 'e.codigo']);

        if ($equipos->isEmpty()) {
            return [];
        }

        $grupos = [];
        $sinCodigo = [];

        foreach ($equipos as $e) {
            $codigo = $this->codigoPreventivo((int) ($e->tipo_equipo_id ?? $e->tipo_linea ?? 0), $e->capacidad_valor, $e->capacidad_unidad);
            if ($codigo === null) {
                $sinCodigo[] = $e->codigo ?? 'equipo';

                continue;
            }
            $grupos[$codigo] = ($grupos[$codigo] ?? 0) + 1;
        }

        ksort($grupos);
        $nombres = DB::table('servicios_catalogo')->whereIn('codigo', array_keys($grupos))->pluck('nombre', 'codigo');

        $salida = [];
        foreach ($grupos as $codigo => $n) {
            $salida[] = [
                'codigo' => $codigo,
                'concepto' => Str::limit(($nombres[$codigo] ?? $codigo) . ($n > 1 ? " × {$n}" : ''), 240),
                'base' => round((float) $tarifas->get($codigo, 0) * $n, 2),
            ];
        }

        if ($sinCodigo !== []) {
            $salida[] = [
                'codigo' => null,
                'concepto' => Str::limit('Equipos sin tipo o capacidad para tarifar: ' . implode(', ', $sinCodigo) . ' (definir el monto)', 240),
                'base' => 0.0,
            ];
        }

        return $salida;
    }

    /** Código del tarifario para el mantenimiento preventivo de un equipo, o null si no se puede determinar. */
    private function codigoPreventivo(int $tipoId, mixed $valor, ?string $unidad): ?string
    {
        if ($tipoId <= 0) {
            return null;
        }

        if (in_array($tipoId, self::TIPOS_POR_CAPACIDAD, true)) {
            if (! is_numeric($valor) || (float) $valor <= 0) {
                return null;
            }

            $btu = strtoupper(trim((string) $unidad)) === 'TR' ? (float) $valor * 12000 : (float) $valor;

            return match (true) {
                $btu <= 12000 => 'T-01',
                $btu <= 18000 => 'T-02',
                $btu <= 24000 => 'T-03',
                $btu <= 36000 => 'T-04',
                $btu <= 48000 => 'T-05',
                default => 'T-06',
            };
        }

        return self::CODIGO_POR_TIPO[$tipoId] ?? null;
    }

    /** @return array<int, array{tecnico_id: int, rol: string, pct: float, monto: float}> */
    private function reparto(float $base, array $m): array
    {
        $sup = $m['supervisores'] ?? [];
        $aux = $m['auxiliares'];
        $pctSup = $sup === [] ? 0.0 : (float) self::PCT_SUPERVISOR;
        $factor = (100 - $pctSup) / 100;
        $filas = [];
        $baseEjec = $base;

        if ($sup !== []) {
            $pctCadaSup = round($pctSup / count($sup), 2);
            $montoCadaSup = round($base * $pctCadaSup / 100, 2);
            $baseEjec = round($base - $montoCadaSup * count($sup), 2);
            foreach ($sup as $id) {
                $filas[] = ['tecnico_id' => $id, 'rol' => 'supervisor', 'pct' => $pctCadaSup, 'monto' => $montoCadaSup];
            }
        }

        if ($aux === []) {
            array_unshift($filas, ['tecnico_id' => $m['lider'], 'rol' => 'lider', 'pct' => round(100 * $factor, 2), 'monto' => round($baseEjec, 2)]);

            return $filas;
        }

        $pctAux = round((100 - self::PCT_LIDER) * $factor / count($aux), 2);
        $montoAux = round($baseEjec * (100 - self::PCT_LIDER) / 100 / count($aux), 2);
        array_unshift($filas, ['tecnico_id' => $m['lider'], 'rol' => 'lider', 'pct' => round(self::PCT_LIDER * $factor, 2), 'monto' => round($baseEjec - $montoAux * count($aux), 2)]);
        foreach ($aux as $a) {
            $filas[] = ['tecnico_id' => $a['id'], 'rol' => $a['rol'], 'pct' => $pctAux, 'monto' => $montoAux];
        }

        return $filas;
    }
}
