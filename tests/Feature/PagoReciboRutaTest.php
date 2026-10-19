<?php

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(fn () => $this->seed(Database\Seeders\RolesSeeder::class));

it('un visitante sin sesión es enviado al login', function () {
    $this->get(route('pagos.recibo', 1))->assertRedirect();
});

it('la ruta del recibo solo acepta ids numéricos', function () {
    $this->get('/pagos/abc/recibo')->assertNotFound();
});

it('un usuario sin rol interno recibe 403', function () {
    $this->actingAs(App\Models\User::factory()->create());

    $this->get(route('pagos.recibo', 1))->assertForbidden();
});

it('un coordinador descarga el recibo en PDF', function () {
    [, $linea] = lineaConPlantilla();
    $pago = app(App\Services\FacturacionService::class)->registrarAnticipo($linea->ordenTrabajo->fresh(), 300.0, 'efectivo');

    $this->actingAs(App\Models\User::factory()->create()->assignRole('coordinador'));

    $respuesta = $this->get(route('pagos.recibo', $pago->id));

    $respuesta->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect($respuesta->getContent())->toStartWith('%PDF');
});

it('un pago inexistente da 404', function () {
    $this->actingAs(App\Models\User::factory()->create()->assignRole('coordinador'));

    $this->get(route('pagos.recibo', 999999))->assertNotFound();
});
