<?php

use App\Models\Area;
use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\Marca;
use App\Models\Sucursal;
use App\Models\TipoEquipo;
use App\Services\ImportadorEquipos;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function csvTemporal(array $lineas): string
{
    $ruta = sys_get_temp_dir() . '/imp_' . uniqid() . '.csv';
    file_put_contents($ruta, implode("\n", $lineas) . "\n");

    return $ruta;
}

beforeEach(function () {
    TipoEquipo::create(['nombre' => 'Split', 'familia' => 'aire_acondicionado', 'prefijo_codigo' => 'HVAC']);
    Marca::create(['nombre' => 'Carrier']);
    $cliente = Cliente::create(['razon_social' => 'Restaurante Demo']);
    $sucursal = Sucursal::create(['cliente_id' => $cliente->id, 'nombre' => 'Centro']);
    Area::create(['sucursal_id' => $sucursal->id, 'nombre' => 'Cocina']);
    $this->enc = 'cliente;sucursal;area;tipo;marca;modelo;serie;capacidad_valor;capacidad_unidad;fecha_instalacion;estado';
});

it('importa filas válidas con código y QR generados', function () {
    $ruta = csvTemporal([
        $this->enc,
        'Restaurante Demo;Centro;Cocina;Split;Carrier;M1;S1;18000;btu;2025-03-15;operativo',
        'restaurante demo;centro;cocina;split;;;;12,5;ton;15/03/2025;',
    ]);

    $r = app(ImportadorEquipos::class)->importar($ruta);

    expect($r['errores'])->toBeEmpty()
        ->and($r['creados'])->toBe(2)
        ->and(Equipo::orderBy('id')->pluck('codigo')->all())->toBe(['HVAC-001', 'HVAC-002'])
        ->and(Equipo::first()->capacidad_unidad)->toBe('BTU')
        ->and((float) Equipo::latest('id')->first()->capacidad_valor)->toBe(12.5)
        ->and(Equipo::first()->token_qr)->toHaveLength(32);
});

it('no importa nada si alguna fila tiene errores y los reporta', function () {
    $ruta = csvTemporal([
        $this->enc,
        'Restaurante Demo;Centro;Cocina;Split;Carrier;M1;S1;18000;BTU;2025-03-15;operativo',
        'Cliente Fantasma;Centro;Cocina;Split;;;;abc;;99/99/2025;raro',
    ]);

    $r = app(ImportadorEquipos::class)->importar($ruta);

    expect($r['creados'])->toBe(0)
        ->and(Equipo::count())->toBe(0)
        ->and(collect($r['errores'])->pluck('fila')->unique()->all())->toBe([3])
        ->and(count($r['errores']))->toBeGreaterThanOrEqual(3);
});

it('rechaza archivos sin las columnas obligatorias', function () {
    $ruta = csvTemporal(['cliente;sucursal', 'A;B']);

    $r = app(ImportadorEquipos::class)->importar($ruta);

    expect($r['creados'])->toBe(0)->and($r['errores'][0]['mensaje'])->toContain('area');
});
