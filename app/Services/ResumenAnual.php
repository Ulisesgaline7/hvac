<?php

namespace App\Services;

use App\Models\Cotizacion;
use App\Models\PlantillaContrato;
use App\Providers\RlsServiceProvider;

/** Equipos x visitas y total del año de una cotización con plan. Nunca lanza excepciones. */
final class ResumenAnual
{
    public const VISITAS = ['PLAN_BASICO' => 2, 'PLAN_ESENCIAL' => 4, 'PLAN_EMPRESARIAL' => 6, 'PLAN_PREMIUM' => 12];

    public const PERIODICIDAD = ['PLAN_BASICO' => 'semestral', 'PLAN_ESENCIAL' => 'trimestral', 'PLAN_EMPRESARIAL' => 'bimestral', 'PLAN_PREMIUM' => 'mensual'];
    private const CUOTAS = ['semestral' => 2, 'trimestral' => 4, 'bimestral' => 6, 'mensual' => 12];

    /** @return ?array{periodicidad:string,cuota:float} Cuota antes de ISV; null si no hay plan. */
    public static function cobro(Cotizacion $c): ?array
    {
        $per = self::PERIODICIDAD[$c->descuento_contrato ?? ''] ?? null;
        $ra = $per ? self::resumen($c) : null;
        if (! $ra) {
            return null;
        }

        return ['periodicidad' => $per, 'cuota' => round($ra['subtotal'] / self::CUOTAS[$per], 2)];
    }

    public static function visitasDePlan(?string $codigo): ?int
    {
        return $codigo ? (self::VISITAS[$codigo] ?? null) : null;
    }

    /** @return ?array{equipos:int,visitas:int,servicios:int,texto:string,subtotal:float,isv:float,total:float} */
    public static function resumen(Cotizacion $c): ?array
    {
        try {
            return RlsServiceProvider::comoInterno(function () use ($c) {
                $v = self::visitasDePlan($c->descuento_contrato) ?? max(0, (int) $c->visitas_anuales);
                if ($v < 2) {
                    return null;
                }

                $e = $c->equipos()->count();
                if ($e === 0) {
                    $e = (int) $c->lineas()->where('tipo', 'servicio')->where('cuenta_equipo', true)->get()
                        ->sum(fn ($l) => (int) ceil((float) $l->cantidad));
                }

                $visita = $c->visita_tratamiento === 'cobrar' ? (float) $c->visita_monto : 0.0;
                $sub = round(((float) $c->subtotal - $visita) * $v + $visita, 2);
                $isv = round($sub * (float) config('tarifario.isv'), 2);

                return [
                    'equipos' => $e,
                    'visitas' => $v,
                    'servicios' => $e * $v,
                    'texto' => $e > 0 ? "{$e} " . ($e === 1 ? 'equipo' : 'equipos') . " × {$v} visitas = " . ($e * $v) . ' servicios al año' : "{$v} visitas al año",
                    'subtotal' => $sub,
                    'isv' => $isv,
                    'total' => round($sub + $isv, 2),
                ];
            });
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Condiciones de la cotización según la plantilla del plan/servicio + condiciones generales. Nunca lanza. */
    public static function condiciones(Cotizacion $c): string
    {
        $generales = array_values(array_filter(
            array_map('strval', (array) config('tarifario.condiciones')),
            fn ($t) => trim($t) !== ''
        ));

        try {
            $pl = PlantillaContrato::paraPlan($c->descuento_contrato);
            if (! $pl) {
                return implode("\n", $generales);
            }

            $esPlan = str_starts_with((string) $pl->clave, 'PLAN_');
            $recurrente = $pl->periodicidad && $pl->periodicidad !== 'unica';

            // Evita repetir la misma línea entre secciones.
            $vistas = [];
            $nuevo = function (string $t) use (&$vistas): bool {
                $k = mb_strtolower(trim(preg_replace('/[^\p{L}\p{N}]+/u', ' ', $t)));
                if ($k === '' || isset($vistas[$k])) {
                    return false;
                }
                $vistas[$k] = true;

                return true;
            };

            $out = [];
            $bloque = function (string $titulo, array $lineas) use (&$out, $nuevo): void {
                $lineas = array_values(array_filter($lineas, fn ($l) => $nuevo($l)));
                if ($lineas) {
                    $out[] = $titulo . ':';
                    foreach ($lineas as $l) {
                        $out[] = '- ' . $l;
                    }
                }
            };
            $limpiar = fn (string $txt): array => array_map(
                fn ($l) => ltrim(trim($l), "-• \t"),
                preg_split('/\R/', trim($txt), -1, PREG_SPLIT_NO_EMPTY) ?: []
            );

            $out[] = ($esPlan ? 'Plan contratado: ' : 'Servicio contratado: ') . $pl->nombre . '.';

            if ($esPlan) {
                $ra = self::resumen($c);
                $visitas = (int) $pl->visitas_anuales;
                if ($ra) {
                    $out[] = 'Alcance: ' . $ra['texto'] . '.';
                } elseif ($visitas > 0) {
                    $out[] = "Alcance: {$visitas} " . ($visitas === 1 ? 'visita' : 'visitas') . ' al año.';
                }
            }

            if ($recurrente) {
                $cobro = self::cobro($c);
                $out[] = 'Facturación ' . $pl->periodicidad
                    . ($cobro && $cobro['cuota'] > 0
                        ? ': cuota de L ' . number_format($cobro['cuota'], 2) . ' + ISV por periodo'
                        : ': se factura por periodo según la cuota indicada en la cotización')
                    . '. Vigencia: 12 meses desde la aceptación.';
            }

            $bloque('Servicios incluidos', $limpiar((string) $pl->servicios_incluidos));
            $bloque('Exclusiones', $limpiar((string) $pl->exclusiones));
            $bloque('Garantías', $limpiar((string) $pl->garantias));

            $sla = [];
            foreach ((array) $pl->sla as $k => $v) {
                if (in_array($k, ['frecuencia', 'visitas_anuales'], true)) {
                    continue; // ya están en el alcance y en servicios incluidos
                }
                $sla[] = ucfirst(str_replace('_', ' ', (string) $k)) . ': ' . (is_scalar($v) ? $v : json_encode($v, JSON_UNESCAPED_UNICODE));
            }
            $bloque('Nivel de servicio', $sla);

            // En planes recurrentes la forma de pago es la cuota, no 50/50.
            $gen = array_filter($generales, fn ($g) => ! ($recurrente && preg_match('/^\s*forma de pago/iu', $g)));
            $bloque('Condiciones generales', array_values($gen));

            return implode("\n", $out);
        } catch (\Throwable $e) {
            return implode("\n", $generales);
        }
    }
}
