<?php

use App\Models\Area;
use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\OrdenTrabajo;
use App\Models\OrdenTrabajoEquipo;
use App\Models\PlantillaFormulario;
use App\Models\Sucursal;
use App\Models\TipoEquipo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

function lineaConPlantilla(): array
{
    $plantilla = PlantillaFormulario::create(['nombre' => 'Prueba ' . uniqid(), 'familia' => 'cuartos_frios']);
    $plantilla->publicar([
        ['clave' => 'motivo', 'etiqueta' => 'Motivo', 'tipo' => 'texto', 'obligatorio' => true],
    ]);

    $tipo = TipoEquipo::create(['nombre' => 'Cámara ' . uniqid(), 'familia' => 'cuartos_frios', 'prefijo_codigo' => 'REF', 'plantilla_id' => $plantilla->id]);
    $cliente = Cliente::create(['razon_social' => 'C ' . uniqid()]);
    $sucursal = Sucursal::create(['cliente_id' => $cliente->id, 'nombre' => 'Matriz']);
    $area = Area::create(['sucursal_id' => $sucursal->id, 'nombre' => 'Cocina']);
    $equipo = Equipo::create(['area_id' => $area->id, 'tipo_equipo_id' => $tipo->id]);
    $ot = OrdenTrabajo::create(['tipo' => 'correctivo', 'cliente_id' => $cliente->id, 'sucursal_id' => $sucursal->id]);
    $ot->equipos()->attach($equipo->id);

    return [$plantilla, OrdenTrabajoEquipo::where('orden_trabajo_id', $ot->id)->first()];
}
