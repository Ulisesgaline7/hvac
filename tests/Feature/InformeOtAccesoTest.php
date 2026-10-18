<?php

use App\Models\Cliente;
use App\Models\OrdenTrabajo;
use App\Models\Sucursal;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function otConCliente(string $estado): array
{
    $cliente = Cliente::create(['razon_social' => 'C ' . uniqid()]);
    $sucursal = Sucursal::create(['cliente_id' => $cliente->id, 'nombre' => 'Matriz']);
    $ot = OrdenTrabajo::create(['tipo' => 'correctivo', 'cliente_id' => $cliente->id, 'sucursal_id' => $sucursal->id]);
    OrdenTrabajo::where('id', $ot->id)->update(['estado' => $estado]);

    return [$cliente, $ot->fresh()];
}

beforeEach(fn () => $this->seed(RolesSeeder::class));

it('el cliente no puede descargar el informe de una OT abierta', function () {
    [$cliente, $ot] = otConCliente('en_proceso');
    $usuario = User::factory()->create(['cliente_id' => $cliente->id]);
    $usuario->assignRole('cliente_admin');

    $this->actingAs($usuario)->get(route('ordenes-trabajo.informe', $ot->id))->assertNotFound();
});

it('el coordinador descarga el informe aunque la OT esté abierta', function () {
    [, $ot] = otConCliente('en_proceso');
    $coord = User::factory()->create();
    $coord->assignRole('coordinador');

    $this->actingAs($coord)->get(route('ordenes-trabajo.informe', $ot->id))->assertOk();
});

it('sin sesión no se puede descargar', function () {
    [, $ot] = otConCliente('cerrada');

    $this->get(route('ordenes-trabajo.informe', $ot->id))->assertRedirect();
});
