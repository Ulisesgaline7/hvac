<?php

use App\Models\CilindroRefrigerante;
use App\Models\MovimientoRefrigerante;
use App\Models\Refrigerante;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function cilindroDePrueba(float $kg = 10): CilindroRefrigerante
{
    $ref = Refrigerante::create(['nombre' => 'R-410A ' . uniqid()]);

    return CilindroRefrigerante::create(['codigo' => 'CIL-' . uniqid(), 'refrigerante_id' => $ref->id, 'peso_inicial_kg' => $kg]);
}

function movimiento(array $linea, ?CilindroRefrigerante $c, string $tipo, float $kg): MovimientoRefrigerante
{
    return MovimientoRefrigerante::create([
        'orden_trabajo_id' => $linea['ot'], 'orden_trabajo_equipo_id' => $linea['id'],
        'cilindro_id' => $c?->id, 'tipo' => $tipo, 'cantidad_kg' => $kg,
    ]);
}

it('calcula el saldo del cilindro con cargas y recuperaciones', function () {
    [, $l] = lineaConPlantilla();
    $linea = ['id' => $l->id, 'ot' => $l->orden_trabajo_id];
    $c = cilindroDePrueba(10);

    movimiento($linea, $c, 'carga', 3);
    movimiento($linea, $c, 'recuperacion', 1.5);

    expect($c->saldoKg())->toBe(8.5);
});

it('no permite cargar más de lo que tiene el cilindro', function () {
    [, $l] = lineaConPlantilla();
    $linea = ['id' => $l->id, 'ot' => $l->orden_trabajo_id];
    $c = cilindroDePrueba(2);

    expect(fn () => movimiento($linea, $c, 'carga', 5))->toThrow(DomainException::class, 'disponibles');
});

it('una carga exige cilindro y una recuperación puede no tenerlo', function () {
    [, $l] = lineaConPlantilla();
    $linea = ['id' => $l->id, 'ot' => $l->orden_trabajo_id];

    movimiento($linea, null, 'recuperacion', 2);
    expect(MovimientoRefrigerante::count())->toBe(1);

    expect(fn () => movimiento($linea, null, 'carga', 1))->toThrow(DomainException::class, 'cilindro');
});
