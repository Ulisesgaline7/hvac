<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reglas_tarifario', function (Blueprint $t) {
            $t->id();
            $t->string('clave', 60)->unique();
            $t->string('etiqueta');
            $t->string('grupo', 60);
            $t->string('tipo', 20); // pct | monto | entero | recargos | descuentos | categorias | condiciones
            $t->jsonb('valor');
            $t->text('ayuda')->nullable();
            $t->unsignedSmallInteger('orden')->default(0);
            $t->timestamps();
        });

        // Se siembra con lo que hoy está en config/tarifario.php: no se pierde ningún valor.
        $cfg = require config_path('tarifario.php');

        $recargos = collect($cfg['recargos'])->map(fn ($r, $k) => [
            'codigo' => $k, 'nombre' => $r['nombre'], 'min' => $r['min'], 'max' => $r['max'],
        ])->values()->all();

        $descuentos = collect($cfg['descuentos'])->map(fn ($r, $k) => [
            'codigo' => $k, 'nombre' => $r['nombre'], 'min' => $r['min'], 'max' => $r['max'], 'desde' => $r['desde'] ?? null,
        ])->values()->all();

        $categorias = collect($cfg['categorias'])->map(fn ($c, $k) => [
            'clave' => $k, 'dias' => $c['dias'], 'corrige' => $c['corrige'], 'cubre' => $c['cubre'], 'no_cubre' => $c['no_cubre'],
        ])->values()->all();

        $filas = [
            ['isv', 'ISV', 'Impuestos', 'pct', $cfg['isv'], 'Impuesto que se suma a cotizaciones y facturas.'],
            ['servicio_minimo', 'Servicio mínimo', 'Cotización', 'monto', $cfg['servicio_minimo'], 'Monto mínimo a cobrar cuando la cotización tiene pocos equipos.'],
            ['minimo_si_menos_de', 'Aplicar servicio mínimo si hay menos de (equipos)', 'Cotización', 'entero', $cfg['minimo_si_menos_de'], null],
            ['viaticos_dia', 'Viáticos por día', 'Cotización', 'monto', $cfg['viaticos_dia'], null],
            ['validez_dias_habiles', 'Validez de la cotización (días hábiles)', 'Cotización', 'entero', $cfg['validez_dias_habiles'], null],
            ['recargos', 'Recargos sobre el servicio', 'Recargos', 'recargos', $recargos, 'Por defecto se usa el mínimo; se puede indicar otro valor dentro del rango.'],
            ['descuentos', 'Descuentos', 'Descuentos', 'descuentos', $descuentos, 'No son acumulables: se aplica el mayor. «Desde» = equipos mínimos para el descuento por volumen.'],
            ['categorias', 'Garantía por categoría', 'Garantía', 'categorias', $categorias, 'Días de garantía de mano de obra y qué cubre cada categoría.'],
            ['condiciones', 'Condiciones de la cotización', 'Condiciones', 'condiciones', $cfg['condiciones'], 'Texto base que se carga en cada cotización nueva.'],
        ];

        foreach ($filas as $i => [$clave, $etiqueta, $grupo, $tipo, $valor, $ayuda]) {
            DB::table('reglas_tarifario')->insert([
                'clave' => $clave, 'etiqueta' => $etiqueta, 'grupo' => $grupo, 'tipo' => $tipo,
                'valor' => json_encode($valor, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'ayuda' => $ayuda, 'orden' => ($i + 1) * 10,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reglas_tarifario');
    }
};
