<?php

use App\Models\Cliente;
use App\Models\Garantia;
use App\Models\OrdenTrabajo;
use App\Models\Sucursal;
use App\Services\GarantiaService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('categoriaPara distingue instalación, correctivo y preventivo', function () {
    $cliente = Cliente::create(['razon_social' => 'C ' . uniqid()]);
    $sucursal = Sucursal::create(['cliente_id' => $cliente->id, 'nombre' => 'Centro']);
    $svc = new GarantiaService();

    $ot = fn (string $tipo) => OrdenTrabajo::create(['tipo' => $tipo, 'cliente_id' => $cliente->id, 'sucursal_id' => $sucursal->id, 'prioridad' => 'normal']);

    expect($svc->categoriaPara($ot('instalacion'), null))->toBe('instalacion')
        ->and($svc->categoriaPara($ot('correctivo'), null))->toBe('correctivo')
        ->and($svc->categoriaPara($ot('emergencia'), null))->toBe('correctivo')
        ->and($svc->categoriaPara($ot('preventivo'), null))->toBe('preventivo');
});

it('emitir sin líneas no crea garantías y no falla', function () {
    $cliente = Cliente::create(['razon_social' => 'C ' . uniqid()]);
    $sucursal = Sucursal::create(['cliente_id' => $cliente->id, 'nombre' => 'Centro']);
    $ot = OrdenTrabajo::create(['tipo' => 'preventivo', 'cliente_id' => $cliente->id, 'sucursal_id' => $sucursal->id, 'prioridad' => 'normal']);

    expect((new GarantiaService())->emitir($ot))->toHaveCount(0)
        ->and(Garantia::count())->toBe(0);
});

it('el informe de la OT incluye las garantías emitidas', function () {
    $cliente = Cliente::create(['razon_social' => 'C ' . uniqid()]);
    $sucursal = Sucursal::create(['cliente_id' => $cliente->id, 'nombre' => 'Centro']);
    $ot = OrdenTrabajo::create(['tipo' => 'correctivo', 'cliente_id' => $cliente->id, 'sucursal_id' => $sucursal->id, 'prioridad' => 'normal']);

    expect(app(\App\Services\InformeOtService::class)->datos($ot)['garantias'])->toBe([]);

    Garantia::create([
        'orden_trabajo_id' => $ot->id, 'categoria' => 'correctivo', 'dias' => 30,
        'desde' => '2026-10-18', 'hasta' => '2026-11-17', 'cubre' => 'La reparación', 'no_cubre' => 'Otras piezas',
    ]);

    $g = app(\App\Services\InformeOtService::class)->datos($ot)['garantias'];
    expect($g)->toHaveCount(1)->and($g[0]['dias'])->toBe(30)->and($g[0]['hasta'])->toBe('17/11/2026');
});

it('la vista del informe muestra el certificado de garantía', function () {
    $cliente = Cliente::create(['razon_social' => 'C ' . uniqid()]);
    $sucursal = Sucursal::create(['cliente_id' => $cliente->id, 'nombre' => 'Centro']);
    $ot = OrdenTrabajo::create(['tipo' => 'correctivo', 'cliente_id' => $cliente->id, 'sucursal_id' => $sucursal->id, 'prioridad' => 'normal']);

    Garantia::create([
        'orden_trabajo_id' => $ot->id, 'categoria' => 'correctivo', 'dias' => 30,
        'desde' => '2026-10-18', 'hasta' => '2026-11-17', 'cubre' => 'La reparación realizada', 'no_cubre' => 'Otras piezas',
    ]);

    $html = view('pdf.informe-ot', app(\App\Services\InformeOtService::class)->datos($ot))->render();

    expect($html)->toContain('Certificado de garantía')->toContain('La reparación realizada')->toContain('17/11/2026');
});

it('el certificado de garantía queda dentro del cuerpo del documento', function () {
    $cliente = Cliente::create(['razon_social' => 'C ' . uniqid()]);
    $sucursal = Sucursal::create(['cliente_id' => $cliente->id, 'nombre' => 'Centro']);
    $ot = OrdenTrabajo::create(['tipo' => 'correctivo', 'cliente_id' => $cliente->id, 'sucursal_id' => $sucursal->id, 'prioridad' => 'normal']);
    Garantia::create([
        'orden_trabajo_id' => $ot->id, 'categoria' => 'correctivo', 'dias' => 30,
        'desde' => '2026-10-18', 'hasta' => '2026-11-17', 'cubre' => 'X', 'no_cubre' => 'Y',
    ]);

    $html = view('pdf.informe-ot', app(\App\Services\InformeOtService::class)->datos($ot))->render();

    expect(stripos($html, 'Certificado de garantía'))->toBeGreaterThan(stripos($html, '<body'))
        ->and(stripos($html, 'Certificado de garantía'))->toBeLessThan(strripos($html, '</body>'));
});
