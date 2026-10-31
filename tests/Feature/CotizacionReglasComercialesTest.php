<?php

use App\Services\CotizacionService;

function equiposLinea(float $cant, float $precio): array
{
    return ['tipo' => 'servicio', 'cantidad' => $cant, 'precio_unitario' => $precio, 'cuenta_equipo' => true];
}

it('ejemplo 1.2: 25 equipos en una sede, mantenimiento puntual (L 21,160)', function () {
    $r = app(CotizacionService::class)->calcular([equiposLinea(25, 800)]);

    expect($r['descuento_codigo'])->toBe('VOL_20_39')
        ->and($r['descuento_monto'])->toBe(1600.0)
        ->and($r['subtotal'])->toBe(18400.0)
        ->and($r['isv'])->toBe(2760.0)
        ->and($r['total'])->toBe(21160.0);
});

it('ejemplo 1.3: equipos de distintas capacidades se suman antes del descuento (L 18,779.50)', function () {
    $r = app(CotizacionService::class)->calcular([equiposLinea(10, 800), equiposLinea(5, 900), equiposLinea(5, 1050)]);

    expect($r['servicios'])->toBe(17750.0)
        ->and($r['descuento_monto'])->toBe(1420.0)
        ->and($r['subtotal'])->toBe(16330.0)
        ->and($r['isv'])->toBe(2449.5)
        ->and($r['total'])->toBe(18779.5);
});

it('ejemplo 2.1: con plan Empresarial se aplica el 12 % del plan y no el 8 % por volumen', function () {
    $r = app(CotizacionService::class)->calcular([equiposLinea(20, 800)], [], 'PLAN_EMPRESARIAL');

    expect($r['descuento_codigo'])->toBe('PLAN_EMPRESARIAL')
        ->and($r['descuento_pct'])->toBe(0.12)
        ->and($r['descuento_monto'])->toBe(1920.0)
        ->and($r['subtotal'])->toBe(14080.0);
});

it('ejemplo 8: 30 equipos con plan Empresarial, por visita (L 21,120 + ISV)', function () {
    $r = app(CotizacionService::class)->calcular([equiposLinea(30, 800)], [], 'PLAN_EMPRESARIAL');

    expect($r['descuento_monto'])->toBe(2880.0)
        ->and($r['subtotal'])->toBe(21120.0)
        ->and($r['isv'])->toBe(3168.0)
        ->and($r['total'])->toBe(24288.0);
});

it('ningún plan queda por debajo del mayor descuento por volumen (10 %), por eso «aplicar el mayor» equivale a la regla del documento', function () {
    foreach (['PLAN_ESENCIAL', 'PLAN_EMPRESARIAL', 'PLAN_PREMIUM'] as $plan) {
        $r = app(CotizacionService::class)->calcular([equiposLinea(40, 800)], [], $plan);

        expect($r['descuento_codigo'])->toBe($plan === 'PLAN_ESENCIAL' ? 'VOL_40' : $plan);
    }
});
