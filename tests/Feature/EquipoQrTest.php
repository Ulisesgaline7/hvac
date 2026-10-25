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

it('sin sesión el QR lleva a elegir cómo ingresar y recuerda el destino', function () {
    $this->get("/e/{$this->equipoA->token_qr}")->assertRedirect(route('equipo.ingreso'));

    expect(session('url.intended'))->toContain("/e/{$this->equipoA->token_qr}");

    $this->get('/ingresar')->assertOk()->assertSee('Soy técnico')->assertSee('Soy cliente');
});

it('un token inexistente muestra mensaje genérico', function () {
    $admin = User::factory()->create();
    $admin->assignRole('administrador');

    $this->actingAs($admin)->get('/e/' . str_repeat('a', 32))
        ->assertNotFound()
        ->assertDontSee('EQ-');
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

it('un técnico sin OT con ese equipo ve solo la ficha mínima, sin datos del cliente', function () {
    $tecnico = User::factory()->create();
    $tecnico->assignRole('tecnico');

    $this->actingAs($tecnico)->get("/e/{$this->equipoA->token_qr}")
        ->assertOk()
        ->assertSee($this->equipoA->codigo)
        ->assertSee('no está en ninguna de sus órdenes')
        ->assertDontSee('Cliente A')
        ->assertDontSee('Cocina');
});

it('un técnico con un token inexistente recibe 404', function () {
    $tecnico = User::factory()->create();
    $tecnico->assignRole('tecnico');

    $this->actingAs($tecnico)->get('/e/' . str_repeat('b', 32))->assertNotFound();
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

function otConEquipo(\App\Models\Equipo $equipo): \App\Models\OrdenTrabajo
{
    $ot = \App\Models\OrdenTrabajo::create([
        'tipo' => \App\Enums\TipoOt::Correctivo,
        'prioridad' => 'normal',
        'estado' => \App\Enums\EstadoOt::Pendiente,
        'cliente_id' => $equipo->area->sucursal->cliente_id,
        'sucursal_id' => $equipo->area->sucursal_id,
    ]);
    $ot->lineas()->create(['equipo_id' => $equipo->id, 'tipo_equipo_id' => $equipo->tipo_equipo_id, 'area_id' => $equipo->area_id]);

    return $ot->fresh();
}

function tecnicoDePrueba(): array
{
    $user = User::factory()->create();
    $user->assignRole('tecnico');

    return [$user, \App\Models\Tecnico::create(['user_id' => $user->id, 'estado_homologacion' => 'aprobado'])];
}

it('el líder y el ayudante ven la ficha completa con su rol; un técnico ajeno solo la mínima', function () {
    [$uLider, $lider] = tecnicoDePrueba();
    [$uAyudante, $ayudante] = tecnicoDePrueba();
    [$uAjeno] = tecnicoDePrueba();

    $ot = otConEquipo($this->equipoA);
    app(\App\Services\CuadrillaService::class)->asignar($ot, $lider->id, [$ayudante->id => 'ayudante']);

    $this->actingAs($uLider)->get("/e/{$this->equipoA->token_qr}")
        ->assertOk()->assertSee('Cliente A')->assertSee($ot->fresh()->numero)->assertSee('Mi rol: Lider');

    $this->actingAs($uAyudante)->get("/e/{$this->equipoA->token_qr}")
        ->assertOk()->assertSee('Cliente A')->assertSee('Mi rol: Ayudante');

    $this->actingAs($uAjeno)->get("/e/{$this->equipoA->token_qr}")
        ->assertOk()->assertDontSee('Cliente A')->assertDontSee($ot->fresh()->numero)
        ->assertSee('no está en ninguna de sus órdenes');
});

it('un técnico con una OT del equipo A no ve datos del equipo B de otro cliente', function () {
    [$u, $t] = tecnicoDePrueba();
    $ot = otConEquipo($this->equipoA);
    app(\App\Services\CuadrillaService::class)->asignar($ot, $t->id, []);

    $this->actingAs($u)->get("/e/{$this->equipoB->token_qr}")
        ->assertOk()->assertDontSee('Cliente B')->assertSee('no está en ninguna de sus órdenes');
});
