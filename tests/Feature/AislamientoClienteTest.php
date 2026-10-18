<?php

use App\Models\Area;
use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\Sucursal;
use App\Models\TipoEquipo;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

function crearCadena(string $nombre, $tipo): array
{
    $cliente = Cliente::create(['razon_social' => $nombre]);
    $sucursal = Sucursal::create(['cliente_id' => $cliente->id, 'nombre' => "Sucursal $nombre"]);
    $area = Area::create(['sucursal_id' => $sucursal->id, 'nombre' => 'Cocina']);
    $equipo = Equipo::create(['area_id' => $area->id, 'tipo_equipo_id' => $tipo->id]);

    return compact('cliente', 'sucursal', 'area', 'equipo');
}

beforeEach(function () {
    $this->seed(RolesSeeder::class);
    $tipo = TipoEquipo::create(['nombre' => 'Split', 'familia' => 'aire_acondicionado', 'prefijo_codigo' => 'HVAC']);
    $this->a = crearCadena('Cliente A', $tipo);
    $this->b = crearCadena('Cliente B', $tipo);
});

it('un usuario de cliente solo ve datos de su propio cliente', function () {
    $usuario = User::factory()->create(['cliente_id' => $this->a['cliente']->id]);
    $usuario->assignRole('cliente_admin');
    $this->actingAs($usuario);

    expect(Cliente::count())->toBe(1)
        ->and(Sucursal::count())->toBe(1)
        ->and(Area::count())->toBe(1)
        ->and(Equipo::count())->toBe(1)
        ->and(Equipo::find($this->b['equipo']->id))->toBeNull()
        ->and(Sucursal::find($this->b['sucursal']->id))->toBeNull()
        ->and(Cliente::find($this->b['cliente']->id))->toBeNull();
});

it('el personal interno ve todos los clientes', function () {
    $coordinador = User::factory()->create();
    $coordinador->assignRole('coordinador');
    $this->actingAs($coordinador);

    expect(Cliente::count())->toBe(2)->and(Equipo::count())->toBe(2);
});

it('un usuario de cliente no puede entrar al panel de administración', function () {
    $usuario = User::factory()->create(['cliente_id' => $this->a['cliente']->id]);
    $usuario->assignRole('cliente_consulta');

    expect($usuario->canAccessPanel(Filament::getPanel('admin')))->toBeFalse();
});

it('registra auditoría con el usuario que hizo el cambio', function () {
    $admin = User::factory()->create();
    $admin->assignRole('administrador');
    $this->actingAs($admin);

    $cliente = Cliente::create(['razon_social' => 'Auditado']);
    $cliente->update(['razon_social' => 'Auditado SA']);

    $registros = Activity::where('subject_type', Cliente::class)->where('subject_id', $cliente->id)->get();

    expect($registros)->toHaveCount(2)
        ->and($registros->first()->causer_id)->toBe($admin->id);
});
