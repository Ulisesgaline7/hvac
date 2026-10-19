<?php

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Models\Factura;
use App\Models\User;
use App\Services\FacturaPdf;
use App\Services\Numeracion\NumeradorFactura;
use Database\Seeders\RolesSeeder;

class NumeradorDePruebaFiscal implements NumeradorFactura
{
    public function asignar(Factura $factura): void
    {
        $factura->numero = 'TEST-' . $factura->id;
    }
}

function facturaParaPdf(): Factura
{
    [, $linea] = lineaConPlantilla();

    return Factura::create([
        'cliente_id' => $linea->ordenTrabajo->cliente_id,
        'fecha' => now()->toDateString(),
        'vence' => now()->addDays(15)->toDateString(),
        'subtotal' => 1000, 'isv' => 150, 'total' => 1150,
        'notas' => 'Trabajo de prueba',
    ]);
}

beforeEach(fn () => $this->seed(RolesSeeder::class));

it('el numerador interno asigna FAC con año y correlativo', function () {
    expect(facturaParaPdf()->fresh()->numero)->toMatch('/^FAC-\d{4}-\d{6}$/');
});

it('el numerador se cambia desde la configuración', function () {
    config(['cobros.numerador' => NumeradorDePruebaFiscal::class]);

    expect(facturaParaPdf()->fresh()->numero)->toStartWith('TEST-');
});

it('el PDF lleva la leyenda de prueba mientras documentos_prueba sea true', function () {
    config(['cobros.documentos_prueba' => true]);

    expect(app(FacturaPdf::class)->html(facturaParaPdf()))->toContain('sin validez fiscal');
});

it('sin modo de prueba y sin CAI no se emite la factura', function () {
    config(['cobros.documentos_prueba' => false]);

    expect(fn () => app(FacturaPdf::class)->html(facturaParaPdf()))->toThrow(DomainException::class);
});

it('con CAI y sin modo de prueba se emite con los datos fiscales y sin leyenda', function () {
    config(['cobros.documentos_prueba' => false]);
    $f = facturaParaPdf();
    $f->forceFill([
        'cai' => 'ABC123-DEF456', 'rango_inicial' => '000-001-01-00000001', 'rango_final' => '000-001-01-00000100',
        'fecha_limite_emision' => now()->addYear()->toDateString(), 'rtn_cliente' => '08019999123456',
    ])->save();

    $html = app(FacturaPdf::class)->html($f->fresh());

    expect($html)->toContain('ABC123-DEF456')->toContain('000-001-01-00000100')->toContain('08019999123456')
        ->and($html)->not->toContain('sin validez fiscal');
});

it('la factura se genera como PDF', function () {
    expect(app(FacturaPdf::class)->render(facturaParaPdf()->fresh()))->toStartWith('%PDF');
});

it('un visitante sin sesión es enviado al login', function () {
    $this->get(route('facturas.pdf', 1))->assertRedirect();
});

it('un usuario sin rol interno recibe 403', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('facturas.pdf', 1))->assertForbidden();
});

it('un coordinador descarga la factura en PDF', function () {
    $f = facturaParaPdf();
    $this->actingAs(User::factory()->create()->assignRole('coordinador'));

    $r = $this->get(route('facturas.pdf', $f->id));

    $r->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect($r->getContent())->toStartWith('%PDF');
});

it('una factura oficial sin CAI responde 409', function () {
    config(['cobros.documentos_prueba' => false]);
    $f = facturaParaPdf();
    $this->actingAs(User::factory()->create()->assignRole('coordinador'));

    $this->get(route('facturas.pdf', $f->id))->assertStatus(409);
});

it('una factura inexistente da 404', function () {
    $this->actingAs(User::factory()->create()->assignRole('coordinador'));

    $this->get(route('facturas.pdf', 999999))->assertNotFound();
});
