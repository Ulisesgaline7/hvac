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

function cadena(string $nombre, TipoEquipo $tipo): array
{
    $cliente = Cliente::create(['razon_social' => $nombre]);
    $sucursal = Sucursal::create(['cliente_id' => $cliente->id, 'nombre' => "Suc $nombre"]);
    $area = Area::create(['sucursal_id' => $sucursal->id, 'nombre' => 'Cocina']);

    return [$cliente, Equipo::create(['area_id' => $area->id, 'tipo_equipo_id' => $tipo->id])];
}

beforeEach(function () {
    $this->seed(RolesSeeder::class);
    $tipo = TipoEquipo::create(['nombre' => 'Split', 'familia' => 'aire_acondicionado', 'prefijo_codigo' => 'HVAC']);
    [$this->clienteA, $this->equipoA] = cadena('Cliente A', $tipo);
    [$this->clienteB, $this->equipoB] = cadena('Cliente B', $tipo);
});

it('sin sesión el QR redirige al login', function () {
    $this->get("/e/{$this->equipoA->token_qr}")->assertRedirect();
});

it('un token inexistente muestra mensaje genérico', function () {
    $admin = User::factory()->create();
    $admin->assignRole('administrador');

    $this->actingAs($admin)->get('/e/' . str_repeat('a', 32))
        ->assertNotFound()
        ->assertDontSee('HVAC-');
});

it('el coordinador ve la ficha completa', function () {
    $coord = User::factory()->create();
    $coord->assignRole('coordinador');

    $this->actingAs($coord)->get("/e/{$this->equipoA->token_qr}")
        ->assertOk()
        ->assertSee($this->equipoA->codigo)
        ->assertSee('Cliente A');
});

it('el cliente ve su equipo pero no el de otro cliente', function () {
    $usuario = User::factory()->create(['cliente_id' => $this->clienteA->id]);
    $usuario->assignRole('cliente_consulta');

    $this->actingAs($usuario)->get("/e/{$this->equipoA->token_qr}")->assertOk();

    $this->actingAs($usuario)->get("/e/{$this->equipoB->token_qr}")
        ->assertNotFound()
        ->assertDontSee('Cliente B');
});

it('un técnico recibe el mensaje genérico hasta que existan las OT', function () {
    $tecnico = User::factory()->create();
    $tecnico->assignRole('tecnico');

    $this->actingAs($tecnico)->get("/e/{$this->equipoA->token_qr}")->assertNotFound();
});

it('el coordinador descarga la etiqueta en PDF', function () {
    $coord = User::factory()->create();
    $coord->assignRole('coordinador');

    $respuesta = $this->actingAs($coord)->get("/etiquetas/equipo/{$this->equipoA->id}");

    $respuesta->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect(substr($respuesta->getContent(), 0, 4))->toBe('%PDF');
});

it('el lote genera un PDF con varios equipos', function () {
    $coord = User::factory()->create();
    $coord->assignRole('coordinador');

    $ids = "{$this->equipoA->id},{$this->equipoB->id}";

    $this->actingAs($coord)->get("/etiquetas/lote?ids=$ids")
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

it('un cliente no puede generar etiquetas', function () {
    $usuario = User::factory()->create(['cliente_id' => $this->clienteA->id]);
    $usuario->assignRole('cliente_admin');

    $this->actingAs($usuario)->get("/etiquetas/equipo/{$this->equipoA->id}")->assertForbidden();
});
