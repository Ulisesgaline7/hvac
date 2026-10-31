<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $planes = [
        ['codigo' => 'PLAN_ESENCIAL', 'nombre' => 'Plan Esencial (4 visitas al año, cada 3 meses)', 'min' => 0.10, 'max' => 0.10, 'desde' => null],
        ['codigo' => 'PLAN_EMPRESARIAL', 'nombre' => 'Plan Empresarial (6 visitas al año, cada 2 meses)', 'min' => 0.12, 'max' => 0.12, 'desde' => null],
        ['codigo' => 'PLAN_PREMIUM', 'nombre' => 'Plan Premium (12 visitas al año, mensual)', 'min' => 0.15, 'max' => 0.15, 'desde' => null],
    ];

    public function up(): void
    {
        // Solo columnas nuevas en una tabla existente: no hay tablas nuevas, así que no se tocan permisos ni RLS.
        Schema::table('cotizaciones', function (Blueprint $t) {
            $t->jsonb('logistica')->nullable();
            $t->jsonb('logistica_detalle')->nullable();
            $t->decimal('logistica_monto', 12, 2)->default(0);
            $t->decimal('viaticos_monto', 12, 2)->default(0);
            $t->unsignedSmallInteger('visitas_anuales')->nullable();
        });

        $filas = [
            ['viaticos_detallados', 'Activar movilización y viáticos detallados', 'entero', 0, '1 = muestra en la cotización los campos de kilómetros, técnicos, días y noches. 0 = se mantiene el cargo de viáticos por día de siempre. No cobre ambos por el mismo trabajo.', 55],
            ['km_tarifa', 'Movilización por kilómetro adicional', 'monto', 12, 'Debe cubrir combustible, desgaste y mantenimiento del vehículo, no solo la gasolina. Valor inicial a validar con costos reales.', 56],
            ['alimentacion_dia', 'Alimentación por técnico por día', 'monto', 350, 'Valor inicial a validar con costos reales.', 57],
            ['hospedaje_noche', 'Hospedaje por técnico por noche', 'monto', 900, 'Presupuesto inicial: si no alcanza en la zona, cotice el costo real.', 58],
        ];

        foreach ($filas as [$clave, $etiqueta, $tipo, $valor, $ayuda, $orden]) {
            if (DB::table('reglas_tarifario')->where('clave', $clave)->exists()) {
                continue;
            }
            DB::table('reglas_tarifario')->insert([
                'clave' => $clave, 'etiqueta' => $etiqueta, 'grupo' => 'Viáticos', 'tipo' => $tipo,
                'valor' => json_encode($valor), 'ayuda' => $ayuda, 'orden' => $orden,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // Los tres planes se agregan a la lista de descuentos que ya existe (se editan en el panel).
        $fila = DB::table('reglas_tarifario')->where('clave', 'descuentos')->first();
        if ($fila) {
            $lista = json_decode($fila->valor, true) ?: [];
            $codigos = array_column($lista, 'codigo');
            foreach ($this->planes as $plan) {
                if (! in_array($plan['codigo'], $codigos, true)) {
                    $lista[] = $plan;
                }
            }
            DB::table('reglas_tarifario')->where('clave', 'descuentos')->update([
                'valor' => json_encode($lista, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => now(),
            ]);
        }

        Cache::forget('reglas_tarifario');
    }

    public function down(): void
    {
        DB::table('reglas_tarifario')->whereIn('clave', ['viaticos_detallados', 'km_tarifa', 'alimentacion_dia', 'hospedaje_noche'])->delete();

        $fila = DB::table('reglas_tarifario')->where('clave', 'descuentos')->first();
        if ($fila) {
            $codigos = array_column($this->planes, 'codigo');
            $lista = array_values(array_filter(json_decode($fila->valor, true) ?: [], fn ($d) => ! in_array($d['codigo'] ?? '', $codigos, true)));
            DB::table('reglas_tarifario')->where('clave', 'descuentos')->update(['valor' => json_encode($lista, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
        }

        Schema::table('cotizaciones', function (Blueprint $t) {
            $t->dropColumn(['logistica', 'logistica_detalle', 'logistica_monto', 'viaticos_monto', 'visitas_anuales']);
        });

        Cache::forget('reglas_tarifario');
    }
};
