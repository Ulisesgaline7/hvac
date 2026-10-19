<?php

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Models\Factura;
use App\Models\OrdenTrabajo;
use App\Services\FacturacionService;
use Illuminate\Support\Facades\DB;

function otParaCobro(array $extra = []): OrdenTrabajo
{
    [, $linea] = lineaConPlantilla();
    $ot = $linea->ordenTrabajo;
    $ot->update(array_merge(['precio_estimado' => 1000], $extra));

    return $ot->fresh();
}

function cerrarOt(OrdenTrabajo $ot): OrdenTrabajo
{
    DB::table('ordenes_trabajo')->where('id', $ot->id)->update(['estado' => 'cerrada']);

    return $ot->fresh();
}

beforeEach(fn () => config(['cobros.momento_factura' => 'al_pagar_completo', 'cobros.dias_credito' => 0]));

it('sin crédito, al cerrar queda como cobro pendiente y no se factura', function () {
    $ot = cerrarOt(otParaCobro());

    expect(app(FacturacionService::class)->generarDesdeOt($ot))->toBeNull()
        ->and(Factura::count())->toBe(0);
});

it('con crédito se factura al cerrar aunque el momento sea al_pagar_completo', function () {
    $ot = cerrarOt(otParaCobro(['dias_credito' => 15]));

    expect(app(FacturacionService::class)->generarDesdeOt($ot))->not->toBeNull();
});

it('el pago que completa el total emite la factura y se lleva los pagos', function () {
    $svc = app(FacturacionService::class);
    $ot = cerrarOt(otParaCobro());

    $svc->generarDesdeOt($ot);
    $pago = $svc->registrarPago($ot, 5000.0, 'efectivo');

    $factura = Factura::where('orden_trabajo_id', $ot->id)->first();
    expect($factura)->not->toBeNull()
        ->and($factura->estado)->toBe('pagada')
        ->and($pago->fresh()->factura_id)->toBe($factura->id);
});

it('un pago parcial no factura y el que completa el total sí', function () {
    $svc = app(FacturacionService::class);
    $ot = cerrarOt(otParaCobro());

    $svc->registrarPago($ot, 100.0, 'efectivo');
    expect(Factura::count())->toBe(0);

    $svc->registrarPago($ot, 5000.0, 'efectivo');
    expect(Factura::where('orden_trabajo_id', $ot->id)->first()?->pagado)->toEqual(5100.0);
});

it('un anticipo que cubre todo antes de cerrar factura al cerrar', function () {
    $svc = app(FacturacionService::class);
    $ot = otParaCobro();

    $svc->registrarAnticipo($ot, 5000.0, 'transferencia');
    expect(Factura::count())->toBe(0);

    $ot = cerrarOt($ot);
    expect($svc->generarDesdeOt($ot))->not->toBeNull();
});

it('un pago sobre una OT abierta no emite factura', function () {
    app(FacturacionService::class)->registrarPago(otParaCobro(), 5000.0, 'efectivo');

    expect(Factura::count())->toBe(0);
});

it('con momento al_cerrar todo sigue como antes', function () {
    config(['cobros.momento_factura' => 'al_cerrar']);
    $ot = cerrarOt(otParaCobro());

    expect(app(FacturacionService::class)->generarDesdeOt($ot))->not->toBeNull();
});
