<?php

use App\Models\Area;
use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\OrdenTrabajo;
use App\Models\OrdenTrabajoEquipo;
use App\Models\PlantillaFormulario;
use App\Models\Sucursal;
use App\Models\TipoEquipo;
use App\Services\FichaService;
use App\Services\FormularioDinamico;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function camposDePrueba(): array
{
    return [
        ['clave' => 'motivo', 'etiqueta' => 'Motivo', 'tipo' => 'texto', 'obligatorio' => true],
        ['clave' => 'temp', 'etiqueta' => 'Temperatura', 'tipo' => 'numero', 'unidad' => '°C', 'min' => -25, 'max' => 5, 'obligatorio' => true],
        ['clave' => 'ok', 'etiqueta' => 'Revisado', 'tipo' => 'casilla', 'obligatorio' => true],
        ['clave' => 'firma', 'etiqueta' => 'Firma', 'tipo' => 'firma', 'obligatorio' => true],
    ];
}

function lineaParaFicha(): array
{
    $plantilla = PlantillaFormulario::create(['nombre' => 'Prueba ' . uniqid(), 'familia' => 'cuartos_frios']);
    $plantilla->publicar(camposDePrueba());

    $tipo = TipoEquipo::create(['nombre' => 'Cámara ' . uniqid(), 'familia' => 'cuartos_frios', 'prefijo_codigo' => 'REF', 'plantilla_id' => $plantilla->id]);
    $cliente = Cliente::create(['razon_social' => 'C ' . uniqid()]);
    $sucursal = Sucursal::create(['cliente_id' => $cliente->id, 'nombre' => 'Matriz']);
    $area = Area::create(['sucursal_id' => $sucursal->id, 'nombre' => 'Cocina']);
    $equipo = Equipo::create(['area_id' => $area->id, 'tipo_equipo_id' => $tipo->id]);
    $ot = OrdenTrabajo::create(['tipo' => 'preventivo', 'cliente_id' => $cliente->id, 'sucursal_id' => $sucursal->id]);
    $ot->equipos()->attach($equipo->id);

    return [$plantilla, OrdenTrabajoEquipo::where('orden_trabajo_id', $ot->id)->first()];
}

it('detecta obligatorios faltantes, incluidas foto y firma', function () {
    expect(FormularioDinamico::faltantes(camposDePrueba(), ['motivo' => 'Fuga']))->toBe(['Temperatura', 'Revisado', 'Firma'])
        ->and(FormularioDinamico::faltantes(camposDePrueba(), ['motivo' => 'x', 'temp' => 3, 'ok' => false, 'firma' => 'ok']))->toBe([]);
});

it('alerta valores fuera de rango sin bloquear', function () {
    $alertas = FormularioDinamico::fueraDeRango(camposDePrueba(), ['temp' => 12]);

    expect($alertas)->toHaveCount(1)->and($alertas[0])->toContain('Temperatura')
        ->and(FormularioDinamico::fueraDeRango(camposDePrueba(), ['temp' => -18]))->toBe([]);
});

it('la ficha conserva la versión con la que se creó aunque la plantilla cambie', function () {
    [$plantilla, $linea] = lineaParaFicha();
    $ficha = app(FichaService::class)->obtenerOCrear($linea);

    $plantilla->publicar([['clave' => 'nuevo', 'etiqueta' => 'Nuevo', 'tipo' => 'texto', 'obligatorio' => true]]);

    expect($plantilla->versiones()->count())->toBe(2)
        ->and($ficha->fresh()->version->version)->toBe(1);
});

it('obtenerOCrear no duplica la ficha de una línea', function () {
    [, $linea] = lineaParaFicha();
    $svc = app(FichaService::class);

    $primera = $svc->obtenerOCrear($linea);
    $segunda = $svc->obtenerOCrear($linea->fresh());

    expect($segunda->id)->toBe($primera->id)
        ->and(App\Models\FichaTecnica::count())->toBe(1);
});

it('guardar marca completitud y alertas', function () {
    [, $linea] = lineaParaFicha();
    $svc = app(FichaService::class);
    $ficha = $svc->obtenerOCrear($linea);

    expect($svc->guardar($ficha, ['motivo' => 'Revisión'])->completa)->toBeFalse();

    $ficha = $svc->guardar($ficha, ['motivo' => 'Revisión', 'temp' => 9, 'ok' => true, 'firma' => 'ok']);
    expect($ficha->completa)->toBeTrue()->and($ficha->alertas)->toHaveCount(1);
});

it('sin plantilla asignada al tipo de equipo lanza un error claro', function () {
    [, $linea] = lineaParaFicha();
    TipoEquipo::query()->update(['plantilla_id' => null]);

    expect(fn () => app(FichaService::class)->obtenerOCrear($linea->fresh()))->toThrow(DomainException::class);
});

const FIRMA_PNG = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

it('la firma en media completa la ficha y permite completar la OT; al borrarla se bloquea', function () {
    Illuminate\Support\Facades\Storage::fake('local');

    [, $linea] = lineaParaFicha();
    $svc = app(FichaService::class);
    $ficha = $svc->obtenerOCrear($linea);
    $ficha = $svc->guardar($ficha, ['motivo' => 'Revisión', 'temp' => 3, 'ok' => true]);
    expect($ficha->completa)->toBeFalse();

    $svc->adjuntar($linea, ['f_firma' => FIRMA_PNG]);
    $ficha = $svc->recalcular($ficha->fresh());
    expect($ficha->completa)->toBeTrue();

    $ot = $linea->ordenTrabajo->fresh();
    $ot->cambiarEstado(App\Enums\EstadoOt::Asignada);
    $ot->cambiarEstado(App\Enums\EstadoOt::EnProceso);
    $ot->cambiarEstado(App\Enums\EstadoOt::CompletadaTecnico);
    expect($ot->fresh()->estado)->toBe(App\Enums\EstadoOt::CompletadaTecnico);

    $linea->fresh()->clearMediaCollection('firma');
    $ficha = $svc->recalcular($ficha->fresh());
    expect($ficha->completa)->toBeFalse()
        ->and($ficha->respuestas)->not->toHaveKey('firma');
});

it('rechaza una firma demasiado grande', function () {
    Illuminate\Support\Facades\Storage::fake('local');

    [, $linea] = lineaParaFicha();

    expect(fn () => app(FichaService::class)->adjuntar($linea, ['f_firma' => 'data:image/png;base64,' . str_repeat('A', 1_500_000)]))
        ->toThrow(DomainException::class);
});
