<?php

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Models\Factura;
use App\Models\OrdenTrabajo;
use App\Models\Pago;
use App\Services\FacturacionService;
use Illuminate\Support\Facades\DB;

function visitaTecnica(array $extra = [], bool $cerrada = true): OrdenTrabajo
{
    [, $linea] = lineaConPlantilla();
    $ot = $linea->ordenTrabajo;
    $ot->update(array_merge(['tipo' => 'visita_tecnica', 'visita_monto' => 500], $extra));

    if ($cerrada) {
        DB::table('ordenes_trabajo')->where('id', $ot->id)->update(['estado' => 'cerrada']);
    }

    return $ot->fresh();
}

it('factura la visita con ISV y sin cotización', function () {
    $visita = visitaTecnica();

    expect(app(FacturacionService::class)->motivoNoFacturarVisita($visita))->toBeNull();

    $f = app(FacturacionService::class)->facturarVisita($visita);

    expect((float) $f->subtotal)->toBe(500.0)
        ->and((float) $f->isv)->toBe(75.0)
        ->and((float) $f->total)->toBe(575.0)
        ->and($f->orden_trabajo_id)->toBe($visita->id)
        ->and($f->cotizacion_id)->toBeNull()
        ->and(app(FacturacionService::class)->motivoNoFacturarVisita($visita->fresh()))->toBe('La visita ya fue facturada.');
});

it('no se puede cobrar dos veces la misma visita', function () {
    $svc = app(FacturacionService::class);
    $visita = visitaTecnica();

    $svc->facturarVisita($visita);

    expect(fn () => $svc->facturarVisita($visita->fresh()))->toThrow(DomainException::class)
        ->and(Factura::count())->toBe(1);
});

it('una visita marcada sin costo no se factura', function () {
    expect(fn () => app(FacturacionService::class)->facturarVisita(visitaTecnica(['visita_cobro' => 'gratis'])))
        ->toThrow(DomainException::class);
});

it('una visita aún abierta no se factura', function () {
    expect(fn () => app(FacturacionService::class)->facturarVisita(visitaTecnica([], false)))
        ->toThrow(DomainException::class);
});

it('una OT que no es visita técnica no se factura como visita', function () {
    [, $linea] = lineaConPlantilla();
    DB::table('ordenes_trabajo')->where('id', $linea->orden_trabajo_id)->update(['estado' => 'cerrada']);

    expect(fn () => app(FacturacionService::class)->facturarVisita($linea->ordenTrabajo->fresh()))
        ->toThrow(DomainException::class);
});

it('una visita con monto cero no se factura', function () {
    expect(fn () => app(FacturacionService::class)->facturarVisita(visitaTecnica(['visita_monto' => 0])))
        ->toThrow(DomainException::class);
});

it('los pagos previos de la visita pasan a la factura', function () {
    $svc = app(FacturacionService::class);
    $visita = visitaTecnica();
    $pago = $svc->registrarPago($visita, 200.0, 'efectivo');

    $f = $svc->facturarVisita($visita->fresh());

    expect($pago->fresh()->factura_id)->toBe($f->id)
        ->and((float) $f->fresh()->pagado)->toBe(200.0);
});


it('una visita con cotización asociada no se factura aparte', function () {
    $ot = otListaParaCerrar();
    $ot->update(['tipo' => 'visita_tecnica', 'visita_monto' => 500]);
    DB::table('ordenes_trabajo')->where('id', $ot->id)->update(['estado' => 'cerrada']);

    App\Models\Cotizacion::create(['cliente_id' => $ot->cliente_id, 'estado' => 'enviada', 'visita_ot_id' => $ot->id]);

    $svc = app(FacturacionService::class);

    expect($svc->motivoNoFacturarVisita($ot->fresh()))->toContain('cotización')
        ->and(fn () => $svc->facturarVisita($ot->fresh()))->toThrow(DomainException::class)
        ->and(Factura::count())->toBe(0);
});

it('si la visita ya se facturó, una cotización posterior no la vuelve a cobrar', function () {
    $ot = otListaParaCerrar();
    $ot->update(['visita_cobro' => 'cobrar', 'visita_monto' => 500]);

    $c = App\Models\Cotizacion::create(['cliente_id' => $ot->cliente_id, 'estado' => 'borrador', 'visita_ot_id' => $ot->id]);

    $visita = new ReflectionMethod(App\Services\CotizacionService::class, 'visita');
    $visita->setAccessible(true);
    $servicio = app(App\Services\CotizacionService::class);

    // Antes de facturar la visita por separado, se suma a la cotización.
    expect($visita->invoke($servicio, $c->fresh()))->toBe(['cobrar', 500.0]);

    Factura::create([
        'cliente_id' => $ot->cliente_id, 'orden_trabajo_id' => $ot->id,
        'fecha' => now()->toDateString(), 'vence' => now()->toDateString(),
        'subtotal' => 500, 'isv' => 75, 'total' => 575,
    ]);

    // Con la visita ya facturada, no se suma ni se bonifica.
    expect($visita->invoke($servicio, $c->fresh()))->toBe([null, 0.0]);
});