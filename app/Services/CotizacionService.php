<?php

namespace App\Services;

use App\Models\Cotizacion;

/** Fórmula del tarifario: Total = [(Cant × Precio) + Recargos − Descuento] + Repuestos + ISV 15 %. */
class CotizacionService
{
    /**
     * @param  array<int,array{tipo?:string,cantidad:float|int|string,precio_unitario:float|int|string,cuenta_equipo?:bool}>  $lineas
     * @param  array<int|string,float|string>  $recargos  lista de códigos (usa el % mínimo) o código => %
     */
    public function calcular(array $lineas, array $recargos = [], ?string $descuentoContrato = null, bool $aplicarMinimo = true): array
    {
        $cfg = config('tarifario');
        $servicios = 0.0;
        $repuestos = 0.0;
        $equipos = 0;

        foreach ($lineas as $l) {
            $importe = round((float) ($l['cantidad'] ?? 0) * (float) ($l['precio_unitario'] ?? 0), 2);

            if (($l['tipo'] ?? 'servicio') === 'repuesto') {
                $repuestos += $importe;

                continue;
            }

            $servicios += $importe;
            if (! empty($l['cuenta_equipo'])) {
                $equipos += (int) ceil((float) ($l['cantidad'] ?? 0));
            }
        }

        $servicios = round($servicios, 2);
        $repuestos = round($repuestos, 2);

        $ajuste = 0.0;
        if ($aplicarMinimo && $equipos > 0 && $equipos < $cfg['minimo_si_menos_de'] && $servicios < $cfg['servicio_minimo']) {
            $ajuste = round($cfg['servicio_minimo'] - $servicios, 2);
        }

        $base = $servicios + $ajuste;

        // Recargos: sobre el servicio; el % queda dentro del rango del tarifario.
        $montos = [];
        foreach ($recargos as $clave => $valor) {
            $codigo = is_int($clave) ? (string) $valor : (string) $clave;
            $def = $cfg['recargos'][$codigo] ?? null;
            if (! $def) {
                continue;
            }
            $pct = is_int($clave) ? $def['min'] : min(max((float) $valor, $def['min']), $def['max']);
            $montos[$codigo] = round($base * $pct, 2);
        }
        $recargosMonto = round(array_sum($montos), 2);

        // Descuento: solo el mayor entre volumen y contrato.
        $descCodigo = null;
        $descPct = 0.0;
        foreach ($cfg['descuentos'] as $codigo => $def) {
            $aplica = isset($def['desde']) ? $equipos >= $def['desde'] : $codigo === $descuentoContrato;
            if ($aplica && $def['min'] > $descPct) {
                $descCodigo = $codigo;
                $descPct = $def['min'];
            }
        }
        $descMonto = round($base * $descPct, 2);

        $subtotal = round($base + $recargosMonto - $descMonto + $repuestos, 2);
        $isv = round($subtotal * $cfg['isv'], 2);

        return [
            'equipos' => $equipos,
            'servicios' => $servicios,
            'ajuste_minimo' => $ajuste,
            'recargos' => $montos,
            'recargos_monto' => $recargosMonto,
            'descuento_codigo' => $descCodigo,
            'descuento_pct' => $descPct,
            'descuento_monto' => $descMonto,
            'repuestos' => $repuestos,
            'subtotal' => $subtotal,
            'isv' => $isv,
            'total' => round($subtotal + $isv, 2),
        ];
    }

    /** Recalcula y guarda los totales de una cotización a partir de sus líneas. */
    public function recalcular(Cotizacion $c): Cotizacion
    {
        $lineas = $c->lineas()->get()->map(fn ($l) => [
            'tipo' => $l->tipo, 'cantidad' => $l->cantidad, 'precio_unitario' => $l->precio_unitario, 'cuenta_equipo' => $l->cuenta_equipo,
        ])->all();

        $r = $this->calcular($lineas, $c->recargos ?? [], $c->descuento_contrato);

        $c->update([
            'subtotal_servicios' => $r['servicios'],
            'ajuste_minimo' => $r['ajuste_minimo'],
            'recargos_monto' => $r['recargos_monto'],
            'descuento_codigo' => $r['descuento_codigo'],
            'descuento_pct' => $r['descuento_pct'],
            'descuento_monto' => $r['descuento_monto'],
            'repuestos_monto' => $r['repuestos'],
            'subtotal' => $r['subtotal'],
            'isv' => $r['isv'],
            'total' => $r['total'],
        ]);

        return $c->fresh();
    }
}
