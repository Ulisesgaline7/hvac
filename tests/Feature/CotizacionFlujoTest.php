<?php

use App\Enums\EstadoOt;
use App\Models\Cliente;
use App\Models\Contrato;
use App\Models\Cotizacion;
use App\Models\OrdenTrabajo;
use App\Models\ServicioCatalogo;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\CotizacionFlujo;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function cotizacionDe(string $categoria, array $extra = [], float $cantidad = 5, float $precio = 800): Cotizacion
{
    $cliente = Cliente::create(['razon_social' => 'Cliente ' . uniqid()]);
    $sucursal = Sucursal::create(['cliente_id' => $cliente->id, 'nombre' => 'Centro']);
    $servicio = ServicioCatalogo::create([
        'codigo' => 'X-' . uniqid(), 'categoria' => $categoria, 'nombre' => "Servicio $categoria",
        'precio' => $precio, 'cuenta_equipo' => true, 'activo' => true,
    ]);

    $c = Cotizacion::create(array_merge(['cliente_id' => $cliente->id, 'sucursal_id' => $sucursal->id], $extra));
    $c->lineas()->create([
        'servicio_id' => $servicio->id, 'tipo' => 'servicio', 'codigo' => $servicio->codigo,
        'descripcion' => $servicio->nombre, 'cantidad' => $cantidad, 'unidad' => 'unidad',
        'precio_unitario' => $precio, 'cuenta_equipo' => true,
    ]);

    return $c->fresh();
}

it('recalcula los totales al guardar líneas', function () {
    $c = cotizacionDe('preventivo');

    expect((float) $c->subtotal_servicios)->toBe(4000.0)
        ->and((float) $c->isv)->toBe(600.0)
        ->and((float) $c->total)->toBe(4600.0)
        ->and($c->numero)->toStartWith('COT-');
});

it('enviar fija estado y vigencia y bloquea las líneas', function () {
    $c = app(CotizacionFlujo::class)->enviar(cotizacionDe('preventivo'));

    expect($c->estado)->toBe('enviada')->and($c->enviada_en)->not->toBeNull()->and($c->valida_hasta)->not->toBeNull();

    expect(fn () => $c->lineas()->create(['descripcion' => 'Extra', 'cantidad' => 1, 'precio_unitario' => 100]))
        ->toThrow(LogicException::class);
});

it('no envía una cotización vacía ni con líneas sin precio', function () {
    $vacia = Cotizacion::create(['cliente_id' => Cliente::create(['razon_social' => 'A'])->id]);
    expect(fn () => app(CotizacionFlujo::class)->enviar($vacia))->toThrow(DomainException::class);

    expect(fn () => app(CotizacionFlujo::class)->enviar(cotizacionDe('correctivo', precio: 0)))->toThrow(DomainException::class);
});

it('aprobar una instalación crea la OT pendiente ligada a la cotización', function () {
    $flujo = app(CotizacionFlujo::class);
    $c = $flujo->enviar(cotizacionDe('instalacion'));

    $r = $flujo->aprobar($c, 'María López');

    $ot = OrdenTrabajo::where('cotizacion_id', $c->id)->first();

    expect($r['contrato'])->toBeNull()
        ->and($r['ordenes'])->toHaveCount(1)
        ->and($ot)->not->toBeNull()
        ->and($ot->tipo->value)->toBe('instalacion')
        ->and($ot->estado)->toBe(EstadoOt::Pendiente)
        ->and($c->fresh()->estado)->toBe('aprobada')
        ->and($c->fresh()->aprobada_por_nombre)->toBe('María López');
});

it('aprobar un preventivo con descuento de contrato crea el contrato en borrador y no crea OT', function () {
    $flujo = app(CotizacionFlujo::class);
    $c = $flujo->enviar(cotizacionDe('preventivo', ['descuento_contrato' => 'CONTRATO_4']));

    $r = $flujo->aprobar($c, 'Pedro Gómez');
    $contrato = Contrato::where('cliente_id', $c->cliente_id)->first();

    expect($r['contrato'])->not->toBeNull()
        ->and($r['ordenes'])->toHaveCount(0)
        ->and($contrato->tipo->value)->toBe('preventivo_recurrente')
        ->and($contrato->estado->value)->toBe('borrador')
        ->and($c->fresh()->contrato_id)->toBe($contrato->id)
        ->and(OrdenTrabajo::where('cotizacion_id', $c->id)->count())->toBe(0);
});

it('no aprueba sin sucursal, en borrador ni dos veces', function () {
    $flujo = app(CotizacionFlujo::class);

    $sinSucursal = $flujo->enviar(cotizacionDe('correctivo'));
    $sinSucursal->update(['sucursal_id' => null]);
    expect(fn () => $flujo->aprobar($sinSucursal->fresh(), 'X'))->toThrow(DomainException::class);

    expect(fn () => $flujo->aprobar(cotizacionDe('correctivo'), 'X'))->toThrow(DomainException::class);

    $c = $flujo->enviar(cotizacionDe('correctivo'));
    $flujo->aprobar($c, 'X');
    expect(fn () => $flujo->aprobar($c->fresh(), 'X'))->toThrow(DomainException::class);
});

it('rechazar guarda el motivo', function () {
    $flujo = app(CotizacionFlujo::class);
    $c = $flujo->rechazar($flujo->enviar(cotizacionDe('correctivo')), 'Precio alto');

    expect($c->estado)->toBe('rechazada')->and($c->notas)->toContain('Precio alto');
});

it('el coordinador descarga el PDF y el cliente no', function () {
    $this->seed(RolesSeeder::class);
    $c = cotizacionDe('preventivo');

    $coord = User::factory()->create();
    $coord->assignRole('coordinador');
    $respuesta = $this->actingAs($coord)->get(route('cotizaciones.pdf', $c));

    $respuesta->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect(substr($respuesta->getContent(), 0, 4))->toBe('%PDF');

    $cliente = User::factory()->create(['cliente_id' => $c->cliente_id]);
    $cliente->assignRole('cliente_admin');
    $this->actingAs($cliente)->get(route('cotizaciones.pdf', $c))->assertForbidden();
});

it('el cliente acepta con el enlace público, firma y se crea la OT', function () {
    $c = cotizacionDe('instalacion');
    $c = app(CotizacionFlujo::class)->enviar($c);

    expect($c->token_aceptacion)->not->toBeEmpty();

    $this->get(route('cotizaciones.aceptacion', $c->token_aceptacion))
        ->assertOk()->assertSee($c->numero);

    $firma = 'data:image/png;base64,' . base64_encode('firma-de-prueba');

    $this->post(route('cotizaciones.aceptar', $c->token_aceptacion), [
        'nombre' => 'Ana Pérez', 'cargo' => 'Gerente', 'acepto' => '1', 'firma' => $firma,
    ])->assertRedirect(route('cotizaciones.aceptacion', $c->token_aceptacion));

    $c->refresh();
    expect($c->estado)->toBe('aprobada')
        ->and($c->aprobada_por_nombre)->toBe('Ana Pérez')
        ->and($c->aprobada_por_cargo)->toBe('Gerente')
        ->and($c->hash_documento)->toHaveLength(64)
        ->and($c->firma_cliente)->toBe($firma)
        ->and(OrdenTrabajo::where('cotizacion_id', $c->id)->count())->toBe(1);

    // Primero ve la confirmación (mensaje flash) y después el enlace queda cerrado.
    $this->get(route('cotizaciones.aceptacion', $c->token_aceptacion))->assertSee('La cotización fue aceptada');
    $this->get(route('cotizaciones.aceptacion', $c->token_aceptacion))->assertSee('Enlace no disponible');
});

it('aceptar exige firma, nombre y la casilla de aceptación', function () {
    $c = app(CotizacionFlujo::class)->enviar(cotizacionDe('preventivo'));

    $this->post(route('cotizaciones.aceptar', $c->token_aceptacion), ['nombre' => 'Ana'])
        ->assertSessionHasErrors(['cargo', 'acepto', 'firma']);

    expect($c->fresh()->estado)->toBe('enviada');
});

it('el cliente puede rechazar con motivo y el enlace deja de funcionar', function () {
    $c = app(CotizacionFlujo::class)->enviar(cotizacionDe('preventivo'));

    $this->post(route('cotizaciones.rechazar', $c->token_aceptacion), ['motivo' => 'Muy caro'])->assertRedirect();

    expect($c->fresh()->estado)->toBe('rechazada')
        ->and($c->fresh()->notas)->toContain('Muy caro');
    $this->post(route('cotizaciones.aceptar', $c->token_aceptacion), [
        'nombre' => 'A', 'cargo' => 'B', 'acepto' => '1', 'firma' => 'data:image/png;base64,AAAA',
    ])->assertNotFound();
});

it('un token inexistente o vencido muestra enlace no disponible', function () {
    $this->get(route('cotizaciones.aceptacion', 'tokeninexistente'))->assertOk()->assertSee('Enlace no disponible');

    $c = app(CotizacionFlujo::class)->enviar(cotizacionDe('preventivo'));
    $c->update(['valida_hasta' => now()->subDay()]);

    $this->get(route('cotizaciones.aceptacion', $c->token_aceptacion))->assertSee('Enlace no disponible');
});

it('el PDF de una cotización aprobada incluye la evidencia de aceptación', function () {
    $c = app(CotizacionFlujo::class)->enviar(cotizacionDe('instalacion'));
    $png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    $html = fn () => view('cotizaciones.pdf', [
        'c' => $c->fresh()->load(['cliente', 'sucursal', 'lineas']),
        'vendedor' => null,
        'empresa' => config('cotizacion.empresa'),
    ])->render();

    expect($html())->not->toContain('Aceptación digital');

    $c->update([
        'estado' => 'aprobada', 'aprobada_en' => now(), 'aprobada_por_nombre' => 'Ana Pérez',
        'aprobada_por_cargo' => 'Gerente', 'aceptada_ip' => '10.0.0.5',
        'hash_documento' => str_repeat('a', 64), 'firma_cliente' => $png,
    ]);

    expect($html())->toContain('Aceptación digital')->toContain('Ana Pérez')->toContain('10.0.0.5')->toContain(str_repeat('a', 64));

    $pdf = app(\App\Services\CotizacionPdf::class)->render($c->fresh());
    expect(substr($pdf, 0, 4))->toBe('%PDF');
});

function visitaCerrada(array $extra = []): OrdenTrabajo
{
    $cliente = Cliente::create(['razon_social' => 'V ' . uniqid()]);
    $sucursal = Sucursal::create(['cliente_id' => $cliente->id, 'nombre' => 'Centro']);

    return OrdenTrabajo::create(array_merge(['tipo' => 'visita_tecnica', 'cliente_id' => $cliente->id, 'sucursal_id' => $sucursal->id, 'prioridad' => 'normal'], $extra));
}

it('una visita previa a una instalación se cobra y suma al total con ISV', function () {
    $visita = visitaCerrada();
    $c = cotizacionDe('instalacion', ['visita_ot_id' => $visita->id]);

    expect($c->visita_tratamiento)->toBe('cobrar')
        ->and((float) $c->visita_monto)->toBe(500.0)
        ->and((float) $c->subtotal)->toBe(4500.0)
        ->and((float) $c->isv)->toBe(675.0)
        ->and((float) $c->total)->toBe(5175.0);
});

it('una visita previa a un correctivo se bonifica y no suma', function () {
    $c = cotizacionDe('correctivo', ['visita_ot_id' => visitaCerrada()->id]);

    expect($c->visita_tratamiento)->toBe('bonificar')
        ->and((float) $c->visita_monto)->toBe(0.0)
        ->and((float) $c->total)->toBe(4600.0);
});

it('la excepción manual de la visita manda sobre la regla', function () {
    $c = cotizacionDe('instalacion', ['visita_ot_id' => visitaCerrada(['visita_cobro' => 'gratis'])->id]);

    expect($c->visita_tratamiento)->toBe('gratis')->and((float) $c->total)->toBe(4600.0);
});

it('la visita aparece en el PDF y en el enlace del cliente, cobrada o sin costo', function () {
    $cobrada = app(CotizacionFlujo::class)->enviar(cotizacionDe('instalacion', ['visita_ot_id' => visitaCerrada()->id]));
    $bonificada = app(CotizacionFlujo::class)->enviar(cotizacionDe('correctivo', ['visita_ot_id' => visitaCerrada()->id]));

    $pdf = fn ($c) => view('cotizaciones.pdf', ['c' => $c->fresh()->load(['cliente', 'sucursal', 'lineas']), 'vendedor' => null, 'empresa' => config('cotizacion.empresa')])->render();

    expect($pdf($cobrada))->toContain('Visita técnica')->toContain('L 500.00')
        ->and($pdf($bonificada))->toContain('Visita técnica (sin costo)');

    $this->get(route('cotizaciones.aceptacion', $cobrada->token_aceptacion))->assertSee('Visita técnica')->assertSee('500.00');
    $this->get(route('cotizaciones.aceptacion', $bonificada->token_aceptacion))->assertSee('Visita técnica (sin costo)');
});
