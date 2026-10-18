<?php

use App\Models\Area;
use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\Sucursal;
use App\Models\TipoEquipo;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('genera código interno consecutivo y token QR opaco', function () {
    $tipo = TipoEquipo::create(['nombre' => 'Split', 'familia' => 'aire_acondicionado', 'prefijo_codigo' => 'HVAC']);
    $cliente = Cliente::create(['razon_social' => 'Cliente Demo']);
    $sucursal = Sucursal::create(['cliente_id' => $cliente->id, 'nombre' => 'Central']);
    $area = Area::create(['sucursal_id' => $sucursal->id, 'nombre' => 'Cocina']);

    $a = Equipo::create(['area_id' => $area->id, 'tipo_equipo_id' => $tipo->id]);
    $b = Equipo::create(['area_id' => $area->id, 'tipo_equipo_id' => $tipo->id]);

    expect($a->codigo)->toBe('HVAC-001')
        ->and($b->codigo)->toBe('HVAC-002')
        ->and($a->token_qr)->toHaveLength(32)
        ->and($a->token_qr)->not->toBe($b->token_qr);
});
