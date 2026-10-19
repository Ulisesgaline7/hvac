<?php

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Models\Pago;
use App\Services\FacturacionService;
use App\Services\ReciboPdf;

function anticipoDePrueba(float $monto = 500.0): Pago
{
    [, $linea] = lineaConPlantilla();

    return app(FacturacionService::class)->registrarAnticipo($linea->ordenTrabajo->fresh(), $monto, 'efectivo', 'REF-1');
}

it('cada pago recibe un número REC con año y correlativo', function () {
    $a = anticipoDePrueba();
    $b = anticipoDePrueba(250.0);

    expect($a->fresh()->numero)->toMatch('/^REC-\d{4}-\d{6}$/')
        ->and($b->fresh()->numero)->toMatch('/^REC-\d{4}-\d{6}$/')
        ->and($a->fresh()->numero)->not->toBe($b->fresh()->numero);
});

it('un anticipo es comprobante de depósito y un pago es recibo', function () {
    $anticipo = anticipoDePrueba();
    $pago = Pago::create(['cliente_id' => $anticipo->cliente_id, 'tipo' => 'pago', 'monto' => 100]);

    $svc = app(ReciboPdf::class);

    expect($svc->titulo($anticipo))->toBe('Comprobante de depósito')
        ->and($svc->titulo($pago->fresh()))->toBe('Recibo de pago')
        ->and($svc->html($anticipo->fresh()))->toContain('no es una factura');
});

it('la leyenda de prueba depende de la configuración', function () {
    $p = anticipoDePrueba();

    config(['cobros.documentos_prueba' => true]);
    expect(app(ReciboPdf::class)->html($p->fresh()))->toContain('sin validez fiscal');

    config(['cobros.documentos_prueba' => false]);
    expect(app(ReciboPdf::class)->html($p->fresh()))->not->toContain('sin validez fiscal');
});

it('el recibo se genera como PDF', function () {
    expect(app(ReciboPdf::class)->render(anticipoDePrueba()->fresh()))->toStartWith('%PDF');
});
