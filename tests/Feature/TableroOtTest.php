<?php

use App\Enums\EstadoOt;
use App\Filament\Pages\TableroOt;
use App\Models\User;
use App\Services\FichaService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function usuarioTablero(string $rol): User
{
    test()->seed(RolesSeeder::class);
    $u = User::factory()->create();
    $u->assignRole($rol);

    return $u;
}

it('solo administrador y coordinador acceden al tablero', function () {
    test()->actingAs(usuarioTablero('coordinador'));
    expect(TableroOt::canAccess())->toBeTrue();

    test()->actingAs(usuarioTablero('tecnico'));
    expect(TableroOt::canAccess())->toBeFalse();
});

it('arrastrar a un estado no permitido no cambia la OT', function () {
    test()->actingAs(usuarioTablero('coordinador'));
    [, $linea] = lineaConPlantilla();
    $ot = $linea->ordenTrabajo;

    Livewire::test(TableroOt::class)->call('moverOt', $ot->id, 'cerrada');

    expect($ot->fresh()->estado)->toBe(EstadoOt::Pendiente);
});

it('un arrastre válido pasa por las guardas y cambia el estado', function () {
    test()->actingAs(usuarioTablero('coordinador'));
    [, $linea] = lineaConPlantilla();
    $ot = $linea->ordenTrabajo;
    app(FichaService::class)->obtenerOCrear($linea)->update(['completa' => true, 'alertas' => []]);
    DB::table('ordenes_trabajo')->where('id', $ot->id)->update(['estado' => 'en_proceso']);

    Livewire::test(TableroOt::class)->call('moverOt', $ot->id, 'completada_tecnico');

    expect($ot->fresh()->estado)->toBe(EstadoOt::CompletadaTecnico);
});

it('un arrastre que exige motivo se rechaza', function () {
    test()->actingAs(usuarioTablero('coordinador'));
    [, $linea] = lineaConPlantilla();
    $ot = $linea->ordenTrabajo;
    DB::table('ordenes_trabajo')->where('id', $ot->id)->update(['estado' => 'en_proceso']);

    Livewire::test(TableroOt::class)->call('moverOt', $ot->id, 'en_espera');

    expect($ot->fresh()->estado)->toBe(EstadoOt::EnProceso);
});
