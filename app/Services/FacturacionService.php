<?php

namespace App\Services;

use App\Models\Cotizacion;
use App\Models\Factura;
use App\Models\OrdenTrabajo;
use App\Models\Pago;
use Illuminate\Support\Facades\Log;

class FacturacionService
{
    /** Genera la factura de una OT recién cerrada. Idempotente: una factura por OT. */
    public function generarDesdeOt(OrdenTrabajo $ot): ?Factura
    {
        $tipo = $this->tipo($ot);

        if (! in_array($tipo, config('cobros.facturan_al_cerrar'), true)) {
            return null;
        }

        if ($existente = Factura::where('orden_trabajo_id', $ot->id)->first()) {
            return $existente;
        }

        $montos = $this->montos($ot);
        if ($montos === null) {
            Log::warning("OT {$ot->id} cerrada sin cotización ni precio: no se generó factura.");

            return null;
        }

        // Sin crédito y con momento «al_pagar_completo»: queda como cobro pendiente
        // y la factura nace al completarse el pago.
        if (! $this->debeFacturarAhora($ot, $montos)) {
            return null;
        }

        $fecha = now()->startOfDay();

        $factura = Factura::create([
            'cliente_id' => $ot->cliente_id,
            'sucursal_id' => $ot->sucursal_id,
            'orden_trabajo_id' => $ot->id,
            'cotizacion_id' => $ot->cotizacion_id,
            'fecha' => $fecha->toDateString(),
            'vence' => $fecha->copy()->addDays($this->diasCredito($ot))->toDateString(),
            'subtotal' => $montos['subtotal'],
            'isv' => $montos['isv'],
            'total' => $montos['total'],
        ]);

        // Los anticipos cobrados antes del cierre pasan a la factura y bajan el saldo.
        Pago::where('orden_trabajo_id', $ot->id)->whereNull('factura_id')->update(['factura_id' => $factura->id]);
        $factura->recalcular();

        return $factura->fresh();
    }

    /** Factura la visita técnica cuando el cliente rechaza la cotización (config visita.si_rechaza). */
    public function facturarVisitaRechazada(Cotizacion $c): ?Factura
    {
        if (! $c->visita_ot_id || config('visita.si_rechaza') !== 'cobrar') {
            return null;
        }

        $ot = OrdenTrabajo::find($c->visita_ot_id);
        if (! $ot || $ot->visita_cobro === 'gratis') {
            return null;
        }

        if ($existente = Factura::where('orden_trabajo_id', $ot->id)->first()) {
            return $existente;
        }

        $subtotal = round((float) ($ot->visita_monto ?? config('visita.costo')), 2);
        if ($subtotal <= 0) {
            return null;
        }

        $isv = round($subtotal * config('tarifario.isv'), 2);
        $fecha = now()->startOfDay();

        $factura = Factura::create([
            'cliente_id' => $ot->cliente_id,
            'sucursal_id' => $ot->sucursal_id,
            'orden_trabajo_id' => $ot->id,
            'cotizacion_id' => $c->id,
            'fecha' => $fecha->toDateString(),
            'vence' => $fecha->copy()->addDays($this->diasCredito($ot, $c))->toDateString(),
            'subtotal' => $subtotal,
            'isv' => $isv,
            'total' => round($subtotal + $isv, 2),
            'notas' => "Visita técnica {$ot->numero} (cotización {$c->numero} rechazada)",
        ]);

        Pago::where('orden_trabajo_id', $ot->id)->whereNull('factura_id')->update(['factura_id' => $factura->id]);
        $factura->recalcular();

        return $factura->fresh();
    }

    /** Anticipo sugerido según el tipo de OT y su monto base. */
    public function anticipoSugerido(OrdenTrabajo $ot): float
    {
        $pct = $this->anticipoPct($ot);
        $base = $this->montos($ot)['total'] ?? 0.0;

        return round($base * $pct / 100, 2);
    }

    public function registrarAnticipo(OrdenTrabajo $ot, float $monto, ?string $metodo = null, ?string $referencia = null): Pago
    {
        return Pago::create([
            'orden_trabajo_id' => $ot->id,
            'cliente_id' => $ot->cliente_id,
            'tipo' => 'anticipo',
            'monto' => $monto,
            'metodo' => $metodo,
            'referencia' => $referencia,
        ]);
    }

    /** Días de crédito del trabajo: OT → cotización → config. 0 = pago contra entrega. */
    public function diasCredito(OrdenTrabajo $ot, ?Cotizacion $cotizacion = null): int
    {
        $cotizacion ??= $ot->cotizacion_id ? Cotizacion::find($ot->cotizacion_id) : null;

        return (int) ($ot->dias_credito ?? $cotizacion?->dias_credito ?? config('cobros.dias_credito'));
    }

    /** % de anticipo del trabajo: OT → cotización → config por tipo de OT. */
    public function anticipoPct(OrdenTrabajo $ot, ?Cotizacion $cotizacion = null): float
    {
        $cotizacion ??= $ot->cotizacion_id ? Cotizacion::find($ot->cotizacion_id) : null;

        return (float) ($ot->anticipo_pct ?? $cotizacion?->anticipo_pct ?? config('cobros.anticipo_pct.' . $this->tipo($ot), 0));
    }

    /** ¿Corresponde emitir la factura ahora? Con crédito o con momento «al_cerrar», sí; si no, cuando lo cobrado cubre el total. */
    public function debeFacturarAhora(OrdenTrabajo $ot, array $montos): bool
    {
        if (config('cobros.momento_factura') === 'al_cerrar' || $this->diasCredito($ot) > 0) {
            return true;
        }

        $cobrado = round((float) Pago::where('orden_trabajo_id', $ot->id)->whereNull('factura_id')->sum('monto'), 2);

        return $cobrado >= round((float) $montos['total'], 2);
    }

    /** Se llama al registrar un pago de una OT sin factura: si la OT está cerrada y el pago ya cubre el total, factura. */
    public function facturarSiPagoCompleto(?OrdenTrabajo $ot): ?Factura
    {
        if (! $ot) {
            return null;
        }

        $estado = $ot->estado instanceof \BackedEnum ? $ot->estado->value : (string) $ot->estado;
        if ($estado !== 'cerrada') {
            return null;
        }

        return $this->generarDesdeOt($ot);
    }

    /** Pago contra una OT cerrada que aún no tiene factura. */
    public function registrarPago(OrdenTrabajo $ot, float $monto, ?string $metodo = null, ?string $referencia = null): Pago
    {
        return Pago::create([
            'orden_trabajo_id' => $ot->id,
            'cliente_id' => $ot->cliente_id,
            'tipo' => 'pago',
            'monto' => $monto,
            'metodo' => $metodo,
            'referencia' => $referencia,
        ]);
    }

    private function tipo(OrdenTrabajo $ot): string
    {
        return $ot->tipo instanceof \BackedEnum ? $ot->tipo->value : (string) $ot->tipo;
    }

    /** @return array{subtotal: float, isv: float, total: float}|null */
    private function montos(OrdenTrabajo $ot): ?array
    {
        // La cotización ya trae ISV y la visita cobrada o bonificada: se copia, no se recalcula.
        if ($ot->cotizacion_id && $cot = Cotizacion::find($ot->cotizacion_id)) {
            return [
                'subtotal' => (float) $cot->subtotal,
                'isv' => (float) $cot->isv,
                'total' => (float) $cot->total,
            ];
        }

        $precio = (float) ($ot->precio_estimado ?? 0);
        if ($precio <= 0) {
            return null;
        }

        if (config('cobros.precio_ot_incluye_isv')) {
            $subtotal = round($precio / (1 + config('tarifario.isv')), 2);

            return ['subtotal' => $subtotal, 'isv' => round($precio - $subtotal, 2), 'total' => round($precio, 2)];
        }

        $isv = round($precio * config('tarifario.isv'), 2);

        return ['subtotal' => round($precio, 2), 'isv' => $isv, 'total' => round($precio + $isv, 2)];
    }
}
