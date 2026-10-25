<?php

use App\Enums\EstadoOt;
use App\Enums\TipoOt;
use App\Models\Area;
use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\OrdenTrabajo;
use App\Models\PlantillaFormulario;
use App\Models\Sucursal;
use App\Models\Tecnico;
use App\Models\TipoEquipo;
use App\Models\User;
use App\Services\CuadrillaService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

const PNG_1X1 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

function tecOff(): array
{
    $u = User::factory()->create();
    $u->assignRole('tecnico');

    return [$u, Tecnico::create(['user_id' => $u->id, 'estado_homologacion' => 'aprobado'])];
}

beforeEach(function () {
    Storage::fake('local');
    $this->seed(RolesSeeder::class);

    $admin = User::factory()->create();
    $this->actingAs($admin);

    $plantilla = PlantillaFormulario::create(['nombre' => 'P off', 'familia' => 'aire_acondicionado']);
    $this->version = $plantilla->publicar([
        ['tipo' => 'texto', 'clave' => 'motivo', 'etiqueta' => 'Motivo', 'obligatorio' => true],
        ['tipo' => 'numero', 'clave' => 'temp', 'etiqueta' => 'Temperatura', 'min' => 10, 'max' => 30, 'obligatorio' => false],
        ['tipo' => 'firma', 'clave' => 'firma_tecnico', 'etiqueta' => 'Firma del técnico', 'obligatorio' => true],
    ]);

    $tipo = TipoEquipo::create(['nombre' => 'Split', 'familia' => 'aire_acondicionado', 'prefijo_codigo' => 'HVAC']);
    $tipo->update(['plantilla_id' => $plantilla->id]);

    $cliente = Cliente::create(['razon_social' => 'Cliente Off']);
    $suc = Sucursal::create(['cliente_id' => $cliente->id, 'nombre' => 'Centro']);
    $area = Area::create(['sucursal_id' => $suc->id, 'nombre' => 'Azotea']);
    $equipo = Equipo::create(['area_id' => $area->id, 'tipo_equipo_id' => $tipo->id]);

    $ot = OrdenTrabajo::create([
        'tipo' => TipoOt::Preventivo, 'prioridad' => 'normal', 'estado' => EstadoOt::Pendiente,
        'cliente_id' => $cliente->id, 'sucursal_id' => $suc->id,
    ]);
    $ot->lineas()->create(['equipo_id' => $equipo->id, 'tipo_equipo_id' => $tipo->id, 'area_id' => $area->id]);

    [$this->uLider, $lider] = tecOff();
    [$this->uAyudante, $ayudante] = tecOff();
    [$this->uAjeno] = tecOff();

    $this->ot = app(CuadrillaService::class)->asignar($ot->fresh(), $lider->id, [$ayudante->id => 'ayudante']);
    $this->linea = $this->ot->lineas()->first();
});

it('el paquete trae las OT, equipos y definición; solo el líder puede editar', function () {
    $r = $this->actingAs($this->uLider)->postJson('/api-tecnico/paquete')->assertOk()->assertHeader('Cache-Control');
    $r->assertJsonPath('ordenes.0.numero', $this->ot->numero)
        ->assertJsonPath('ordenes.0.editable', true)
        ->assertJsonPath('ordenes.0.lineas.0.id', $this->linea->id)
        ->assertJsonPath('ordenes.0.lineas.0.ficha.version_id', $this->version->id);
    expect($r->json("plantillas.{$this->version->id}"))->toHaveCount(3);

    $this->actingAs($this->uAyudante)->postJson('/api-tecnico/paquete')
        ->assertOk()->assertJsonPath('ordenes.0.editable', false);

    $this->actingAs($this->uAjeno)->postJson('/api-tecnico/paquete')->assertOk()->assertJsonCount(0, 'ordenes');
});

it('sin sesión o sin ser técnico no hay paquete', function () {
    auth()->logout();
    $this->postJson('/api-tecnico/paquete')->assertUnauthorized();

    $admin = User::factory()->create();
    $admin->assignRole('administrador');
    $this->actingAs($admin)->postJson('/api-tecnico/paquete')->assertForbidden();
});

it('el líder sincroniza la ficha: el servidor recalcula alertas y completitud, y reintentar no duplica', function () {
    $cuerpo = [
        'linea_id' => $this->linea->id,
        'version_id' => $this->version->id,
        'respuestas' => ['motivo' => 'Falla', 'temp' => 50, 'inventada' => 'x'],
        'firmas' => ['firma_tecnico' => PNG_1X1],
    ];

    $r = $this->actingAs($this->uLider)->postJson('/api-tecnico/ficha', $cuerpo)->assertOk();
    $r->assertJsonPath('completa', true)->assertJsonPath('respuestas.motivo', 'Falla');
    expect($r->json('alertas'))->not->toBeEmpty()
        ->and($r->json('respuestas'))->not->toHaveKey('inventada');

    $this->actingAs($this->uLider)->postJson('/api-tecnico/ficha', $cuerpo)->assertOk();

    expect($this->linea->fresh()->getMedia('firma_tecnico'))->toHaveCount(1);
});

it('sin la firma obligatoria la ficha queda incompleta', function () {
    $this->actingAs($this->uLider)->postJson('/api-tecnico/ficha', [
        'linea_id' => $this->linea->id, 'version_id' => $this->version->id, 'respuestas' => ['motivo' => 'Falla'],
    ])->assertOk()->assertJsonPath('completa', false);
});

it('ayudante y técnico ajeno no pueden enviar la ficha', function () {
    $cuerpo = ['linea_id' => $this->linea->id, 'version_id' => $this->version->id, 'respuestas' => ['motivo' => 'X']];

    $this->actingAs($this->uAyudante)->postJson('/api-tecnico/ficha', $cuerpo)->assertForbidden();
    expect($this->actingAs($this->uAjeno)->postJson('/api-tecnico/ficha', $cuerpo)->status())->toBeIn([403, 404]);
});

it('rechaza versión distinta, OT cerrada y firmas que no son PNG', function () {
    $base = ['linea_id' => $this->linea->id, 'version_id' => $this->version->id, 'respuestas' => ['motivo' => 'X']];

    $this->actingAs($this->uLider)->postJson('/api-tecnico/ficha', ['version_id' => 9999] + $base)->assertStatus(409);

    $this->actingAs($this->uLider)->postJson('/api-tecnico/ficha', $base + ['firmas' => ['firma_tecnico' => 'data:text/html;base64,AAAA']])
        ->assertStatus(422);

    $this->ot->forceFill(['estado' => EstadoOt::Cancelada])->saveQuietly();
    $this->actingAs($this->uLider)->postJson('/api-tecnico/ficha', $base)->assertStatus(422);
});
