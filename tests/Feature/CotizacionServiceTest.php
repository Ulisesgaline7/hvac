<?php

use App\Services\CotizacionService;

function lineaServicio(float $cant, float $precio, bool $equipo = true): array
{
    return ['tipo' => 'servicio', 'cantidad' => $cant, 'precio_unitario' => $precio, 'cuenta_equipo' => $equipo];
}

it('aplica recargos al servicio, descuento por volumen y deja los repuestos fuera de ambos', function () {
    $r = app(CotizacionService::class)->calcular(
        [lineaServicio(10, 800), ['tipo' => 'repuesto', 'cantidad' => 1, 'precio_unitario' => 500]],
        ['ALTURA'],
    );

    // 8,000 servicio + 1,200 altura (15 %) − 400 volumen (5 %, 10 equipos) + 500 repuestos
    expect($r['servicios'])->toBe(8000.0)
        ->and($r['recargos_monto'])->toBe(1200.0)
        ->and($r['descuento_codigo'])->toBe('VOL_10_19')
        ->and($r['descuento_monto'])->toBe(400.0)
        ->and($r['subtotal'])->toBe(9300.0)
        ->and($r['isv'])->toBe(1395.0)
        ->and($r['total'])->toBe(10695.0);
});

it('completa el servicio mínimo de L 1,500 con menos de 3 equipos', function () {
    $r = app(CotizacionService::class)->calcular([lineaServicio(1, 800)]);

    expect($r['ajuste_minimo'])->toBe(700.0)
        ->and($r['subtotal'])->toBe(1500.0)
        ->and($r['isv'])->toBe(225.0)
        ->and($r['total'])->toBe(1725.0);
});

it('no aplica el mínimo cuando son 3 equipos o más', function () {
    $r = app(CotizacionService::class)->calcular([lineaServicio(3, 400)]);

    expect($r['ajuste_minimo'])->toBe(0.0)->and($r['subtotal'])->toBe(1200.0);
});

it('los descuentos no se acumulan: se aplica el mayor', function () {
    $r = app(CotizacionService::class)->calcular([lineaServicio(20, 800)], [], 'CONTRATO_4');

    // volumen 8 % vs contrato de 4 visitas 12 % -> 12 % de 16,000
    expect($r['descuento_codigo'])->toBe('CONTRATO_4')
        ->and($r['descuento_monto'])->toBe(1920.0)
        ->and($r['subtotal'])->toBe(14080.0)
        ->and($r['total'])->toBe(16192.0);
});

it('un recargo con porcentaje propio queda dentro del rango del tarifario', function () {
    $r = app(CotizacionService::class)->calcular([lineaServicio(5, 1000)], ['NOCTURNO_DOMINICAL' => 0.9]);

    // máximo del tarifario: 50 %
    expect($r['recargos']['NOCTURNO_DOMINICAL'])->toBe(2500.0);
});
