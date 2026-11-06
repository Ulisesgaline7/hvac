<?php

namespace App\Services;

use App\Models\Cotizacion;

/** Fórmula del tarifario: Total = [(Cant × Precio) + Recargos − Descuento] + Repuestos + ISV 15 %. */
class CotizacionService
{
    /** Recargos que no se combinan: se aplica el de mayor porcentaje. */
    private const EXCLUYENTES = ['NOCTURNO_DOMINICAL', 'EMERGENCIA_24_7'];

    /** Los recargos sumados no pasan de este porcentaje del servicio. */
    private const TOPE_RECARGOS = 1.0;

    /**
     * @param  array<int,array{tipo?:string,categoria?:?string,cantidad:float|int|string,precio_unitario:float|int|string,cuenta_equipo?:bool}>  $lineas
     * @param  array<int|string,float|string>  $recargos  lista de códigos (usa el % mínimo) o código => %
     */
    public function calcular(array $lineas, array $recargos = [], ?string $descuentoContrato = null, bool $aplicarMinimo = true, ?float $pctContrato = null): array
    {
        $cfg = config('tarifario');
        $servicios = 0.0;
        $repuestos = 0.0;
        $equipos = 0;
        $hayInstalacion = false;

        foreach ($lineas as $l) {
            $importe = round((float) ($l['cantidad'] ?? 0) * (float) ($l['precio_unitario'] ?? 0), 2);

            if (($l['tipo'] ?? 'servicio') === 'repuesto') {
                $repuestos += $importe;

                continue;
            }

            if (($l['categoria'] ?? null) === 'instalacion') {
                $hayInstalacion = true;
            }

            $servicios += $importe;
            if (! empty($l['cuenta_equipo'])) {
                $equipos += (int) ceil((float) ($l['cantidad'] ?? 0));
            }
        }

        $servicios = round($servicios, 2);
        $repuestos = round($repuestos, 2);

        // Servicio mínimo: no aplica a instalaciones.
        $ajuste = 0.0;
        if ($aplicarMinimo && ! $hayInstalacion && $equipos > 0 && $equipos < $cfg['minimo_si_menos_de'] && $servicios < $cfg['servicio_minimo']) {
            $ajuste = round($cfg['servicio_minimo'] - $servicios, 2);
        }

        $base = $servicios + $ajuste;

        // Recargos: porcentaje dentro del rango del tarifario.
        $pcts = [];
        foreach ($recargos as $clave => $valor) {
            $codigo = is_int($clave) ? (string) $valor : (string) $clave;
            $def = $cfg['recargos'][$codigo] ?? null;
            if (! $def) {
                continue;
            }
            $pcts[$codigo] = is_int($clave) ? $def['min'] : min(max((float) $valor, $def['min']), $def['max']);
        }

        // Nocturno y emergencia no se combinan: queda el mayor.
        $presentes = array_values(array_intersect(self::EXCLUYENTES, array_keys($pcts)));
        if (count($presentes) > 1) {
            usort($presentes, fn ($a, $b) => $pcts[$b] <=> $pcts[$a]);
            foreach (array_slice($presentes, 1) as $descartado) {
                unset($pcts[$descartado]);
            }
        }

        // Tope: si la suma lo pasa, se reduce en proporción.
        $suma = array_sum($pcts);
        if ($suma > self::TOPE_RECARGOS && $suma > 0) {
            $factor = self::TOPE_RECARGOS / $suma;
            $pcts = array_map(fn ($p) => $p * $factor, $pcts);
        }

        $montos = [];
        foreach ($pcts as $codigo => $pct) {
            $montos[$codigo] = round($base * $pct, 2);
        }
        $recargosMonto = round(array_sum($montos), 2);

        // Descuento: solo el mayor entre volumen y contrato, sobre la base de servicios.
        $descCodigo = null;
        $descPct = 0.0;
        foreach ($cfg['descuentos'] as $codigo => $def) {
            $aplica = isset($def['desde']) ? $equipos >= $def['desde'] : $codigo === $descuentoContrato;
            $pct = $def['min'];
            if ($aplica && ! isset($def['desde']) && $pctContrato !== null) {
                $pct = min(max($pctContrato, $def['min']), $def['max'] ?? $def['min']);
            }
            if ($aplica && $pct > $descPct) {
                $descCodigo = $codigo;
                $descPct = $pct;
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
        $lineas = $c->lineas()->with('servicio')->get()->map(fn ($l) => [
            'tipo' => $l->tipo,
            'categoria' => $l->servicio?->categoria,
            'cantidad' => $l->cantidad,
            'precio_unitario' => $l->precio_unitario,
            'cuenta_equipo' => $l->cuenta_equipo,
        ])->all();

        $r = $this->calcular($lineas, $c->recargos ?? [], $c->descuento_contrato, true, $c->descuento_contrato_pct !== null ? (float) $c->descuento_contrato_pct : null);

        [$tratamiento, $visita] = $this->visita($c);
        $extra = (new LogisticaViaticos)->calcular($c->logistica ?? [], config('tarifario'));
        $adicional = round($visita + $extra['total'], 2);
        $subtotal = round($r['subtotal'] + $adicional, 2);
        $isv = $adicional > 0 ? round($subtotal * config('tarifario.isv'), 2) : $r['isv'];
        $total = round($subtotal + $isv, 2);

        $c->update([
            'subtotal_servicios' => $r['servicios'],
            'ajuste_minimo' => $r['ajuste_minimo'],
            'recargos_monto' => $r['recargos_monto'],
            'descuento_codigo' => $r['descuento_codigo'],
            'descuento_pct' => $r['descuento_pct'],
            'descuento_monto' => $r['descuento_monto'],
            'repuestos_monto' => $r['repuestos'],
            'subtotal' => $subtotal,
            'isv' => $isv,
            'total' => $total,
            'visita_tratamiento' => $tratamiento,
            'visita_monto' => $visita,
            'logistica_monto' => $extra['movilizacion'],
            'viaticos_monto' => $extra['viaticos'],
            'logistica_detalle' => $extra['total'] > 0 ? $extra['detalle'] : null,
        ]);

        return $c->fresh();
    }

    /** @return array{0: ?string, 1: float} tratamiento (bonificar|cobrar|gratis|null) y monto a sumar. */
    private function visita(Cotizacion $c): array
    {
        if (! $c->visita_ot_id) {
            return [null, 0.0];
        }

        $ot = \App\Models\OrdenTrabajo::find($c->visita_ot_id);
        if (! $ot) {
            return [null, 0.0];
        }

        // Visita ya facturada por separado: no se vuelve a sumar ni a bonificar.
        if (\App\Models\Factura::where('orden_trabajo_id', $ot->id)->exists()) {
            return [null, 0.0];
        }

        $categoria = $c->lineas()->with('servicio')->get()->map(fn ($l) => $l->servicio?->categoria)->filter()->first();

        $tratamiento = match (true) {
            $ot->visita_cobro === 'gratis' => 'gratis',
            $ot->visita_cobro === 'cobrar' => 'cobrar',
            $categoria !== null => config("visita.si_aprueba.{$categoria}", 'cobrar'),
            default => null,
        };

        $monto = $tratamiento === 'cobrar' ? (float) ($ot->visita_monto ?? config('visita.costo')) : 0.0;

        return [$tratamiento, round($monto, 2)];
    }
}
