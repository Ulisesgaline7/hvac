<?php

use App\Models\Area;
use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\Sucursal;
use App\Models\TipoEquipo;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('las páginas de edición con relation managers cargan para el administrador', function () {
    $this->seed(RolesSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('administrador');

    $tipo = TipoEquipo::create(['nombre' => 'Split', 'familia' => 'aire_acondicionado', 'prefijo_codigo' => 'HVAC']);
    $cliente = Cliente::create(['razon_social' => 'Demo']);
    $sucursal = Sucursal::create(['cliente_id' => $cliente->id, 'nombre' => 'Centro']);
    $area = Area::create(['sucursal_id' => $sucursal->id, 'nombre' => 'Cocina']);
    Equipo::create(['area_id' => $area->id, 'tipo_equipo_id' => $tipo->id]);

    $this->actingAs($admin);

    $this->get("/admin/clientes/{$cliente->id}/edit")->assertOk();
    $this->get("/admin/sucursales/{$sucursal->id}/edit")->assertOk();
    $this->get("/admin/areas/{$area->id}/edit")->assertOk();
    $this->get('/admin/equipos')->assertOk();
});
