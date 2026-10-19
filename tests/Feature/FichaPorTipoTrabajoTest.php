<?php

use App\Services\FichaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function tipoTrabajoDeFicha($ficha): ?string
{
    $plantillaId = DB::table('plantilla_versiones')->where('id', $ficha->plantilla_version_id)->value('plantilla_id');

    return DB::table('plantillas_formulario')->where('id', $plantillaId)->value('tipo_trabajo');
}

it('un correctivo usa la ficha de correctivo', function () {
    [, $linea] = lineaConPlantilla();
    $linea->ordenTrabajo->update(['tipo' => 'correctivo']);

    $ficha = app(FichaService::class)->obtenerOCrear($linea->fresh());

    expect(tipoTrabajoDeFicha($ficha))->toBe('correctivo');
});

it('una visita técnica usa la ficha de diagnóstico', function () {
    [, $linea] = lineaConPlantilla();
    $linea->ordenTrabajo->update(['tipo' => 'visita_tecnica']);

    $ficha = app(FichaService::class)->obtenerOCrear($linea->fresh());

    expect(tipoTrabajoDeFicha($ficha))->toBe('diagnostico');
});

it('un preventivo sigue usando la ficha del tipo de equipo', function () {
    [, $linea] = lineaConPlantilla();
    $linea->ordenTrabajo->update(['tipo' => 'preventivo']);

    $ficha = app(FichaService::class)->obtenerOCrear($linea->fresh());

    expect(tipoTrabajoDeFicha($ficha))->toBe('mantenimiento');
});
