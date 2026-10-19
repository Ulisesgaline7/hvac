<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

function comoAppAdjuntos(array $vars, Closure $fn): mixed
{
    $c = DB::connection('rls_owner');
    $c->statement('SET ROLE hvac_app');
    $c->select("select set_config('app.es_interno', ?, false), set_config('app.cliente_id', ?, false), set_config('app.user_id', ?, false)", [
        $vars['interno'] ?? '', (string) ($vars['cliente'] ?? ''), (string) ($vars['user'] ?? ''),
    ]);

    try {
        return $fn($c);
    } finally {
        $c->statement('RESET ROLE');
        $c->select("select set_config('app.es_interno', '', false), set_config('app.cliente_id', '', false), set_config('app.user_id', '', false)");
    }
}

function filaMedia(int $lineaId, string $nombre): array
{
    return [
        'model_type' => 'App\Models\OrdenTrabajoEquipo', 'model_id' => $lineaId, 'uuid' => (string) Illuminate\Support\Str::uuid(),
        'collection_name' => 'fotos', 'name' => $nombre, 'file_name' => "$nombre.jpg", 'disk' => 'local', 'size' => 10,
        'manipulations' => '[]', 'custom_properties' => '[]', 'generated_conversions' => '[]', 'responsive_images' => '[]',
    ];
}

beforeEach(function () {
    static $migrado = false;

    config(['database.connections.rls_owner' => array_merge(config('database.connections.pgsql'), [
        'database' => 'hvac_manager_test',
    ])]);

    try {
        DB::connection('rls_owner')->getPdo();
    } catch (Throwable $e) {
        $this->markTestSkipped('Postgres de pruebas no disponible: ' . $e->getMessage());
    }

    if (! $migrado) {
        Artisan::call('migrate:fresh', ['--database' => 'rls_owner', '--force' => true]);
        $migrado = true;
    }

    $c = DB::connection('rls_owner');
    $c->statement('TRUNCATE media, actas_conformidad, trabajos_adicionales, fichas_tecnicas, plantilla_versiones, plantillas_formulario, historial_estados_ot, ordenes_trabajo, orden_trabajo_equipo, documentos_tecnico, tecnicos, contrato_equipo, planes_preventivos, contratos, equipos, areas, sucursales, clientes, tipos_equipo, users RESTART IDENTITY CASCADE');

    $tipo = $c->table('tipos_equipo')->insertGetId(['nombre' => 'Split', 'familia' => 'aire_acondicionado', 'prefijo_codigo' => 'HVAC']);
    $plantilla = $c->table('plantillas_formulario')->insertGetId(['nombre' => 'P', 'familia' => 'aire_acondicionado']);
    $version = $c->table('plantilla_versiones')->insertGetId(['plantilla_id' => $plantilla, 'version' => 1, 'definicion' => '[]']);

    $this->d = [];

    foreach (['a', 'b'] as $k) {
        $cli = $c->table('clientes')->insertGetId(['razon_social' => "Cliente $k"]);
        $suc = $c->table('sucursales')->insertGetId(['cliente_id' => $cli, 'nombre' => "Suc $k"]);
        $area = $c->table('areas')->insertGetId(['sucursal_id' => $suc, 'nombre' => 'Cocina']);
        $eq = $c->table('equipos')->insertGetId(['codigo' => "HVAC-$k", 'token_qr' => str_repeat($k, 32), 'area_id' => $area, 'tipo_equipo_id' => $tipo]);
        $user = $c->table('users')->insertGetId(['name' => "Tec $k", 'email' => "tec-$k@test.com", 'password' => 'x']);
        $tec = $c->table('tecnicos')->insertGetId(['user_id' => $user, 'estado_homologacion' => 'aprobado']);

        $this->d[$k] = ['cli' => $cli, 'user' => $user, 'tec' => $tec];

        foreach (['abierta' => 'en_proceso', 'cerrada' => 'cerrada'] as $clave => $estado) {
            $ot = $c->table('ordenes_trabajo')->insertGetId([
                'numero' => "OT-$k-$clave", 'tipo' => 'correctivo', 'estado' => $estado,
                'cliente_id' => $cli, 'sucursal_id' => $suc, 'tecnico_id' => $tec,
            ]);
            $linea = $c->table('orden_trabajo_equipo')->insertGetId(['orden_trabajo_id' => $ot, 'equipo_id' => $eq]);

            $c->table('fichas_tecnicas')->insert(['orden_trabajo_id' => $ot, 'orden_trabajo_equipo_id' => $linea, 'plantilla_version_id' => $version]);
            $c->table('actas_conformidad')->insert(['orden_trabajo_id' => $ot]);
            $c->table('trabajos_adicionales')->insert(['orden_trabajo_id' => $ot, 'hallazgo' => "H $k $clave"]);
            $c->table('media')->insert(filaMedia($linea, "m-$k-$clave"));

            $this->d[$k][$clave] = ['ot' => $ot, 'linea' => $linea];
        }
    }
});

const TABLAS_ADJUNTOS = ['fichas_tecnicas', 'actas_conformidad', 'trabajos_adicionales', 'media'];

it('el cliente solo ve fichas, actas, trabajos y archivos de OT cerradas', function () {
    comoAppAdjuntos(['interno' => 'false', 'cliente' => $this->d['a']['cli']], function ($c) {
        foreach (TABLAS_ADJUNTOS as $tabla) {
            expect($c->table($tabla)->count())->toBe(1, "tabla $tabla");
        }

        expect($c->table('media')->value('name'))->toBe('m-a-cerrada')
            ->and($c->table('trabajos_adicionales')->value('hallazgo'))->toBe('H a cerrada');
    });
});

it('un cliente no ve nada del otro cliente aunque su OT esté cerrada', function () {
    comoAppAdjuntos(['interno' => 'false', 'cliente' => $this->d['a']['cli']], function ($c) {
        expect($c->table('media')->where('name', 'like', 'm-b-%')->count())->toBe(0)
            ->and($c->table('trabajos_adicionales')->where('hallazgo', 'like', 'H b%')->count())->toBe(0)
            ->and($c->table('actas_conformidad')->where('orden_trabajo_id', $this->d['b']['cerrada']['ot'])->count())->toBe(0);
    });
});

it('un cliente no puede subir archivos ni crear actas', function () {
    comoAppAdjuntos(['interno' => 'false', 'cliente' => $this->d['a']['cli']], function ($c) {
        expect(fn () => $c->table('media')->insert(filaMedia($this->d['a']['cerrada']['linea'], 'intruso')))->toThrow(QueryException::class);
        expect(fn () => $c->table('actas_conformidad')->insert(['orden_trabajo_id' => $this->d['a']['cerrada']['ot']]))->toThrow(QueryException::class);
    });
});

it('el técnico ve lo de todas sus OT y nada de otro técnico', function () {
    comoAppAdjuntos(['interno' => 'false', 'user' => $this->d['a']['user']], function ($c) {
        foreach (TABLAS_ADJUNTOS as $tabla) {
            expect($c->table($tabla)->count())->toBe(2, "tabla $tabla");
        }

        expect($c->table('media')->where('name', 'like', 'm-b-%')->count())->toBe(0);
    });
});

it('el técnico puede adjuntar en su OT pero no en la de otro técnico', function () {
    comoAppAdjuntos(['interno' => 'false', 'user' => $this->d['a']['user']], function ($c) {
        $c->table('media')->insert(filaMedia($this->d['a']['abierta']['linea'], 'propio'));
        $c->table('actas_conformidad')->insert(['orden_trabajo_id' => $this->d['a']['abierta']['ot']]);

        expect(fn () => $c->table('media')->insert(filaMedia($this->d['b']['abierta']['linea'], 'ajeno')))->toThrow(QueryException::class);
        expect(fn () => $c->table('actas_conformidad')->insert(['orden_trabajo_id' => $this->d['b']['abierta']['ot']]))->toThrow(QueryException::class);
    });
});

it('el personal interno ve todo y sin contexto no se ve nada', function () {
    comoAppAdjuntos(['interno' => 'true'], function ($c) {
        foreach (TABLAS_ADJUNTOS as $tabla) {
            expect($c->table($tabla)->count())->toBe(4, "tabla $tabla");
        }
    });

    comoAppAdjuntos([], function ($c) {
        foreach (TABLAS_ADJUNTOS as $tabla) {
            expect($c->table($tabla)->count())->toBe(0, "tabla $tabla");
        }
    });
});

it('el cliente solo ve las firmas de actas y trabajos de OT cerradas; el interno ve todas', function () {
    $o = DB::connection('rls_owner');

    foreach (['abierta', 'cerrada'] as $clave) {
        $ot = $this->d['a'][$clave]['ot'];
        $acta = $o->table('actas_conformidad')->where('orden_trabajo_id', $ot)->value('id');
        $trabajo = $o->table('trabajos_adicionales')->where('orden_trabajo_id', $ot)->value('id');

        $o->table('media')->insert(array_merge(filaMedia(0, "firma-acta-$clave"), [
            'model_type' => App\Models\ActaConformidad::class, 'model_id' => $acta,
        ]));
        $o->table('media')->insert(array_merge(filaMedia(0, "firma-trab-$clave"), [
            'model_type' => App\Models\TrabajoAdicional::class, 'model_id' => $trabajo,
        ]));
    }

    comoAppAdjuntos(['interno' => 'false', 'cliente' => $this->d['a']['cli']], function ($c) {
        $nombres = $c->table('media')->where('name', 'like', 'firma-%')->pluck('name')->all();
        sort($nombres);

        expect($nombres)->toBe(['firma-acta-cerrada', 'firma-trab-cerrada']);
    });

    comoAppAdjuntos(['interno' => 'true'], function ($c) {
        expect($c->table('media')->where('name', 'like', 'firma-%')->count())->toBe(4);
    });
});
