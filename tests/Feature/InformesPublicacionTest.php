<?php

use App\Models\Cliente;
use App\Models\Informe;
use App\Models\OrdenTrabajo;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\PublicadorInforme;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function otParaInforme(string $estado = 'cerrada'): array
{
    $cliente = Cliente::create(['razon_social' => 'C ' . uniqid()]);
    $sucursal = Sucursal::create(['cliente_id' => $cliente->id, 'nombre' => 'Matriz']);
    $ot = OrdenTrabajo::create(['tipo' => 'correctivo', 'cliente_id' => $cliente->id, 'sucursal_id' => $sucursal->id]);
    DB::table('ordenes_trabajo')->where('id', $ot->id)->update(['estado' => $estado]);

    return [$cliente, $ot->fresh()];
}

function usuarioConRol(string $rol, ?int $clienteId = null): User
{
    $u = User::factory()->create(['cliente_id' => $clienteId]);
    $u->assignRole($rol);

    return $u;
}

beforeEach(function () {
    $this->seed(RolesSeeder::class);
    Storage::fake('local');
});

it('solo se genera el informe de una OT cerrada', function () {
    [, $ot] = otParaInforme('en_proceso');

    expect(fn () => app(PublicadorInforme::class)->generar($ot))->toThrow(DomainException::class);
});

it('genera un borrador con PDF guardado y conserva el token al regenerar', function () {
    [, $ot] = otParaInforme();
    $svc = app(PublicadorInforme::class);

    $informe = $svc->generar($ot);
    expect($informe->estado)->toBe('borrador')
        ->and(strlen($informe->token_verificacion))->toBe(48);
    Storage::disk('local')->assertExists($informe->ruta_pdf);

    expect($svc->generar($ot)->token_verificacion)->toBe($informe->token_verificacion);
});

it('un técnico no publica; el coordinador sí; un publicado no se regenera', function () {
    [, $ot] = otParaInforme();
    $informe = app(PublicadorInforme::class)->generar($ot);

    $this->actingAs(usuarioConRol('tecnico'));
    expect(fn () => app(PublicadorInforme::class)->publicar($informe))->toThrow(DomainException::class, 'Solo coordinador');

    $this->actingAs(usuarioConRol('coordinador'));
    $publicado = app(PublicadorInforme::class)->publicar($informe);
    expect($publicado->estado)->toBe('publicado')->and($publicado->publicado_en)->not->toBeNull();

    expect(fn () => app(PublicadorInforme::class)->generar($ot->fresh()))->toThrow(DomainException::class);
});

it('el cliente solo descarga el informe una vez publicado, y solo el suyo', function () {
    [$cliente, $ot] = otParaInforme();
    $usuario = usuarioConRol('cliente_admin', $cliente->id);
    $informe = app(PublicadorInforme::class)->generar($ot);

    $this->actingAs($usuario)->get(route('ordenes-trabajo.informe', $ot->id))->assertNotFound();

    $this->actingAs(usuarioConRol('coordinador'));
    app(PublicadorInforme::class)->publicar($informe);

    $this->actingAs($usuario)->get(route('ordenes-trabajo.informe', $ot->id))
        ->assertOk()->assertHeader('Content-Type', 'application/pdf');

    [$otroCliente] = otParaInforme();
    $this->actingAs(usuarioConRol('cliente_admin', $otroCliente->id))
        ->get(route('ordenes-trabajo.informe', $ot->id))->assertNotFound();
});

it('la página pública de verificación solo confirma informes publicados', function () {
    [, $ot] = otParaInforme();
    $informe = app(PublicadorInforme::class)->generar($ot);

    $this->get(route('informe.verificar', $informe->token_verificacion))->assertNotFound();

    $this->actingAs(usuarioConRol('coordinador'));
    app(PublicadorInforme::class)->publicar($informe);

    $this->get(route('informe.verificar', $informe->token_verificacion))->assertOk()->assertSee($ot->numero);
    $this->get(route('informe.verificar', 'tokeninexistente123'))->assertNotFound();
});
