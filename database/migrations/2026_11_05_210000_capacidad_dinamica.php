<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $unidades = [
            'aire_acondicionado' => ['BTU/h', 'TR', 'kW'],
            'cuartos_frios' => ['HP', 'BTU/h', 'kW'],
            'refrigeracion_comercial' => ['HP', 'BTU/h', 'kW'],
        ];

        foreach (DB::table('tipos_equipo')->get(['id', 'familia', 'campos_registro']) as $t) {
            if (! isset($unidades[$t->familia])) {
                continue;
            }
            $campos = json_decode($t->campos_registro ?? '[]', true) ?: [];
            $campos = array_values(array_filter($campos, fn ($c) => ! in_array($c['clave'] ?? '', ['btu', 'capacidad', 'capacidad_unidad'], true)));

            array_unshift(
                $campos,
                ['tipo' => 'numero', 'clave' => 'capacidad', 'etiqueta' => 'Capacidad', 'obligatorio' => false],
                ['tipo' => 'seleccion', 'clave' => 'capacidad_unidad', 'etiqueta' => 'Unidad de capacidad', 'obligatorio' => false, 'opciones' => $unidades[$t->familia]],
            );

            DB::table('tipos_equipo')->where('id', $t->id)
                ->update(['campos_registro' => json_encode($campos, JSON_UNESCAPED_UNICODE)]);
        }

        foreach (DB::table('equipos')->whereNotNull('capacidad_valor')->get(['id', 'capacidad_valor', 'capacidad_unidad', 'datos_placa']) as $e) {
            $d = json_decode($e->datos_placa ?? '[]', true) ?: [];
            $d['capacidad'] = $e->capacidad_valor + 0;
            if ($e->capacidad_unidad) {
                $d['capacidad_unidad'] = $e->capacidad_unidad;
            }
            DB::table('equipos')->where('id', $e->id)->update(['datos_placa' => json_encode($d, JSON_UNESCAPED_UNICODE)]);
        }
    }

    public function down(): void
    {
    }
};
