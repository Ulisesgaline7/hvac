<?php

use App\Models\Cotizacion;
use App\Models\Factura;
use App\Services\FacturacionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('copia los montos de la cotización, con ISV y visita ya incluidos', function () {
    $ot = otListaParaCerrar();
    $cot = Cotizacion::create([
        'cliente_id' => $ot->cliente_id, 'estado' => 'aprobada',
        'subtotal' => 1500, 'isv' => 225, 'total' => 1725, 'visita_monto' => 500,
    ]);
    $ot->update(['cotizacion_id' => $cot->id]);

    $f = app(FacturacionService::class)->generarDesdeOt($ot->fresh());

    expect($f)->toBeInstanceOf(Factura::class)
        ->and((float) $f->total)->toBe(1725.0)
        ->and($f->estado)->toBe('emitida')
        ->and($f->saldo)->toBe(1725.0)
        ->and($f->numero)->toStartWith('FAC-');
});

it('sin cotización usa el precio de la OT y suma ISV', function () {
    $ot = otListaParaCerrar();
    $ot->update(['precio_estimado' => 1000]);

    $f = app(FacturacionService::class)->generarDesdeOt($ot->fresh());

    expect((float) $f->subtotal)->toBe(1000.0)
        ->and((float) $f->total)->toBe(round(1000 * (1 + config('tarifario.isv')), 2));
});

it('sin cotización ni precio no genera factura', function () {
    $ot = otListaParaCerrar();
    $ot->update(['precio_estimado' => null]);

    expect(app(FacturacionService::class)->generarDesdeOt($ot->fresh()))->toBeNull();
    expect(Factura::count())->toBe(0);
});

it('no duplica la factura de una OT', function () {
    $ot = otListaParaCerrar();
    $ot->update(['precio_estimado' => 1000]);
    $svc = app(FacturacionService::class);

    $a = $svc->generarDesdeOt($ot->fresh());
    $b = $svc->generarDesdeOt($ot->fresh());

    expect($b->id)->toBe($a->id)->and(Factura::count())->toBe(1);
});

it('el anticipo previo se aplica a la factura y baja el saldo', function () {
    $ot = otListaParaCerrar();
    $ot->update(['precio_estimado' => 1000]);
    $svc = app(FacturacionService::class);

    $svc->registrarAnticipo($ot->fresh(), 500);
    $f = $svc->generarDesdeOt($ot->fresh());

    expect((float) $f->pagado)->toBe(500.0)
        ->and($f->estado)->toBe('parcial')
        ->and($f->saldo)->toBe(round(1150 - 500, 2));
});

it('un pago que cubre el total deja la factura pagada', function () {
    $ot = otListaParaCerrar();
    $ot->update(['precio_estimado' => 1000]);
    $f = app(FacturacionService::class)->generarDesdeOt($ot->fresh());

    $f->pagos()->create(['cliente_id' => $f->cliente_id, 'tipo' => 'pago', 'monto' => $f->total]);

    expect($f->fresh()->estado)->toBe('pagada')->and($f->fresh()->saldo)->toBe(0.0);
});
