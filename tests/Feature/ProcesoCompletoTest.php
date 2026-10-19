<?php

use App\Enums\EstadoOt;
use App\Models\Area;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\CotizacionLinea;
use App\Models\Equipo;
use App\Models\Garantia;
use App\Models\PlantillaFormulario;
use App\Models\ServicioCatalogo;
use App\Models\Sucursal;
use App\Models\Tecnico;
use App\Models\TipoEquipo;
use App\Models\TrabajoAdicional;
use App\Models\User;
use App\Services\CotizacionFlujo;
use App\Services\FichaService;
use App\Services\GeneradorQr;
use App\Services\PublicadorInforme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('recorre el proceso: cotización, equipos con QR, OT, ejecución, cierre, garantía e informe', function () {
    Storage::fake('local');

    // --- Cliente, sucursal y tipo de equipo con su formulario
    $plantilla = PlantillaFormulario::create(['nombre' => 'Proceso ' . uniqid(), 'familia' => 'aire_acondicionado']);
    $plantilla->publicar([
        ['clave' => 'motivo', 'etiqueta' => 'Motivo de la visita', 'tipo' => 'texto', 'obligatorio' => true],
    ]);
    $tipo = TipoEquipo::create(['nombre' => 'Split ' . uniqid(), 'familia' => 'aire_acondicionado', 'prefijo_codigo' => 'HVAC', 'plantilla_id' => $plantilla->id]);
    $cliente = Cliente::create(['razon_social' => 'Clínica San José']);
    $sucursal = Sucursal::create(['cliente_id' => $cliente->id, 'nombre' => 'Matriz']);
    $area = Area::create(['sucursal_id' => $sucursal->id, 'nombre' => 'Consultorios']);

    // --- 1. Cotización: 3 equipos de 12,000 BTU del catálogo
    $servicio = ServicioCatalogo::where('codigo', 'T-01')->firstOrFail();
    $cot = Cotizacion::create(['cliente_id' => $cliente->id, 'sucursal_id' => $sucursal->id]);
    CotizacionLinea::create([
        'cotizacion_id' => $cot->id, 'servicio_id' => $servicio->id, 'tipo' => 'servicio', 'codigo' => $servicio->codigo,
        'descripcion' => $servicio->nombre, 'cantidad' => 3, 'precio_unitario' => $servicio->precio, 'cuenta_equipo' => true,
    ]);

    $flujo = app(CotizacionFlujo::class);
    $cot = $flujo->enviar($cot);

    expect($cot->estado)->toBe('enviada')
        ->and($cot->token_aceptacion)->not->toBeNull()
        ->and((float) $cot->subtotal)->toBe(2400.0)
        ->and((float) $cot->isv)->toBe(360.0)
        ->and((float) $cot->total)->toBe(2760.0);

    ['contrato' => $contrato, 'ordenes' => $ordenes] = $flujo->aprobar($cot, 'María Pérez');

    expect($cot->fresh()->estado)->toBe('aprobada')
        ->and($contrato)->toBeNull()
        ->and($ordenes)->toHaveCount(1)
        ->and($ordenes->first()->cotizacion_id)->toBe($cot->id);

    // --- 2. Equipos registrados con código y QR
    $equipos = collect(range(1, 3))->map(fn () => Equipo::create([
        'area_id' => $area->id, 'tipo_equipo_id' => $tipo->id, 'capacidad_valor' => 12000, 'capacidad_unidad' => 'BTU',
    ]));

    expect($equipos->pluck('codigo')->unique())->toHaveCount(3)
        ->and(strlen($equipos[0]->token_qr))->toBe(32)
        ->and(app(GeneradorQr::class)->dataUriDeUrl('https://hvac.test/q/' . $equipos[0]->token_qr))->toStartWith('data:image');

    // --- 3. La OT creada al aprobar recibe los equipos y se asigna
    $ot = $ordenes->first();
    $ot->equipos()->attach($equipos->pluck('id')->all());

    $user = User::create(['name' => 'Técnico Flujo', 'email' => 'tec-flujo@test.com', 'password' => 'x']);
    $tecnico = Tecnico::create(['user_id' => $user->id, 'estado_homologacion' => 'aprobado']);

    $ot->update(['tecnico_id' => $tecnico->id, 'fecha_programada' => now()->addDay()->toDateString(), 'hora_programada' => '09:00']);
    $ot->fresh()->cambiarEstado(EstadoOt::Asignada, 'Asignación de técnico');

    // --- 4. Ejecución: el técnico llena las fichas y completa
    $ot = $ot->fresh();
    $ot->cambiarEstado(EstadoOt::EnProceso, 'Llegada al sitio');

    foreach ($ot->lineas as $linea) {
        $ficha = app(FichaService::class)->obtenerOCrear($linea);
        app(FichaService::class)->guardar($ficha, ['motivo' => 'Mantenimiento preventivo programado']);
    }

    $ot->fresh()->cambiarEstado(EstadoOt::CompletadaTecnico);

    // Acta firmada por el cliente y un trabajo adicional aprobado
    firmarActa($ot);
    $adicional = TrabajoAdicional::create([
        'orden_trabajo_id' => $ot->id, 'fecha' => now(), 'hallazgo' => 'Capacitor dañado',
        'trabajo_propuesto' => 'Cambio de capacitor', 'decision' => 'aprobado', 'decidido_por_nombre' => 'María Pérez',
        'detalle_costo' => [['concepto' => 'Capacitor', 'cantidad' => 2, 'precio_unitario' => 500]],
    ]);
    expect((float) $adicional->total)->toBe(1150.0);

    $ot->fresh()->cambiarEstado(EstadoOt::EnRevision);

    // --- 5. El supervisor (coordinador) cierra
    test()->actingAs(usuarioTablero('coordinador'));
    $ot->fresh()->cambiarEstado(EstadoOt::Cerrada, 'Revisado y aprobado');

    $ot = $ot->fresh();
    expect($ot->estado)->toBe(EstadoOt::Cerrada)->and($ot->cerrada_en)->not->toBeNull();

    // Garantía: una por equipo, preventivo = 30 días
    $garantias = Garantia::where('orden_trabajo_id', $ot->id)->get();
    expect($garantias)->toHaveCount(3)
        ->and($garantias->pluck('categoria')->unique()->all())->toBe(['preventivo'])
        ->and($garantias->pluck('dias')->unique()->all())->toBe([30])
        ->and($garantias->first()->desde->addDays(30)->toDateString())->toBe($garantias->first()->hasta->toDateString());

    // Informe PDF con token de verificación
    $informe = app(PublicadorInforme::class)->generar($ot);
    expect($informe->estado)->toBe('borrador')
        ->and($informe->token_verificacion)->not->toBeNull()
        ->and(Storage::disk('local')->exists($informe->ruta_pdf))->toBeTrue();
});
