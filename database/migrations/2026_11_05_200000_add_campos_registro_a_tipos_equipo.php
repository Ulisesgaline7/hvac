<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tipos_equipo', function (Blueprint $t) {
            $t->jsonb('campos_registro')->nullable()->after('plantilla_id');
        });

        $familias = [
            'aire_acondicionado' => [
                ['tipo' => 'numero', 'clave' => 'seer', 'etiqueta' => 'SEER', 'obligatorio' => false, 'min' => 8, 'max' => 40],
                ['tipo' => 'numero', 'clave' => 'btu', 'etiqueta' => 'Capacidad', 'unidad' => 'BTU/h', 'obligatorio' => false],
                ['tipo' => 'seleccion', 'clave' => 'tipo_compresor', 'etiqueta' => 'Tipo de compresor', 'obligatorio' => false, 'opciones' => ['Scroll', 'Rotativo', 'Reciprocante', 'Inverter']],
                ['tipo' => 'seleccion', 'clave' => 'fases', 'etiqueta' => 'Fases', 'obligatorio' => false, 'opciones' => ['Monofásico', 'Trifásico']],
            ],
            'cuartos_frios' => [
                ['tipo' => 'numero', 'clave' => 'temperatura_diseno', 'etiqueta' => 'Temperatura de diseño', 'unidad' => '°C', 'obligatorio' => false, 'min' => -40, 'max' => 15],
                ['tipo' => 'seleccion', 'clave' => 'tipo_deshielo', 'etiqueta' => 'Tipo de deshielo', 'obligatorio' => false, 'opciones' => ['Eléctrico', 'Gas caliente', 'Por aire']],
                ['tipo' => 'numero', 'clave' => 'ventiladores_evaporador', 'etiqueta' => 'Ventiladores del evaporador', 'obligatorio' => false],
                ['tipo' => 'seleccion', 'clave' => 'fases', 'etiqueta' => 'Fases', 'obligatorio' => false, 'opciones' => ['Monofásico', 'Trifásico']],
            ],
            'refrigeracion_comercial' => [
                ['tipo' => 'numero', 'clave' => 'temperatura_operacion', 'etiqueta' => 'Temperatura de operación', 'unidad' => '°C', 'obligatorio' => false, 'min' => -30, 'max' => 15],
                ['tipo' => 'seleccion', 'clave' => 'tipo_compresor', 'etiqueta' => 'Tipo de compresor', 'obligatorio' => false, 'opciones' => ['Hermético', 'Semihermético', 'Scroll']],
                ['tipo' => 'seleccion', 'clave' => 'tipo_deshielo', 'etiqueta' => 'Tipo de deshielo', 'obligatorio' => false, 'opciones' => ['Eléctrico', 'Gas caliente', 'Por aire']],
            ],
        ];

        foreach ($familias as $familia => $campos) {
            DB::table('tipos_equipo')->where('familia', $familia)
                ->update(['campos_registro' => json_encode($campos, JSON_UNESCAPED_UNICODE)]);
        }
    }

    public function down(): void
    {
        Schema::table('tipos_equipo', fn (Blueprint $t) => $t->dropColumn('campos_registro'));
    }
};
