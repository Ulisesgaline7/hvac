<?php

use App\Services\LogisticaViaticos;

function tarifasPropuestas(): array
{
    return ['km_tarifa' => 12, 'alimentacion_dia' => 350, 'hospedaje_noche' => 900];
}

it('calcula el ejemplo de dos técnicos fuera de Tegucigalpa (L 6,280)', function () {
    $r = (new LogisticaViaticos)->calcular(
        ['km' => 240, 'tecnicos' => 2, 'dias' => 2, 'noches' => 1, 'peajes' => 200],
        tarifasPropuestas(),
    );

    expect($r['movilizacion'])->toBe(2880.0)
        ->and($r['alimentacion'])->toBe(1400.0)
        ->and($r['hospedaje'])->toBe(1800.0)
        ->and($r['peajes'])->toBe(200.0)
        ->and($r['viaticos'])->toBe(3400.0)
        ->and($r['total'])->toBe(6280.0);
});

it('sin hospedaje no cobra noches, y un viaje del mismo día no genera hospedaje', function () {
    $r = (new LogisticaViaticos)->calcular(['km' => 100, 'tecnicos' => 2, 'dias' => 1, 'noches' => 0], tarifasPropuestas());

    expect($r['hospedaje'])->toBe(0.0)->and($r['alimentacion'])->toBe(700.0)->and($r['total'])->toBe(1900.0);
});

it('movilización adicional de 80 km por ciclo (ejemplo del documento)', function () {
    $r = (new LogisticaViaticos)->calcular(['km' => 80], tarifasPropuestas());

    expect($r['movilizacion'])->toBe(960.0)->and($r['viaticos'])->toBe(0.0)->and($r['total'])->toBe(960.0);
});

it('datos vacíos o negativos no generan cargos', function () {
    $r = (new LogisticaViaticos)->calcular(['km' => -5, 'tecnicos' => '', 'dias' => null], tarifasPropuestas());

    expect($r['total'])->toBe(0.0);
    expect((new LogisticaViaticos)->calcular([], tarifasPropuestas())['total'])->toBe(0.0);
});

it('guarda las tarifas usadas para poder reproducir la cotización', function () {
    $r = (new LogisticaViaticos)->calcular(['km' => 10, 'tecnicos' => 1, 'dias' => 1], tarifasPropuestas());

    expect($r['detalle']['tarifa_km'])->toBe(12.0)->and($r['detalle']['tarifa_alimentacion'])->toBe(350.0);
});

it('calcula el total anual de un plan', function () {
    // 6 visitas de L 21,120 + ISV 15 % = L 145,728 (ejemplo 8 del documento)
    expect(LogisticaViaticos::anual(21120.0, 6, 0.15))->toBe(['subtotal' => 126720.0, 'isv' => 19008.0, 'total' => 145728.0]);
    // con L 960 de movilización por ciclo = L 152,352 (ejemplo 9)
    expect(LogisticaViaticos::anual(22080.0, 6, 0.15)['total'])->toBe(152352.0);
});
