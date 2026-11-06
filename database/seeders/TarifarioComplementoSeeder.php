<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Complementa el tarifario rev. 2: ventilador/termostato comercial, cocina industrial, frases de condiciones y alineación del plan de 4 visitas. */
class TarifarioComplementoSeeder extends Seeder
{
    public function run(): void
    {
        $filas = [
            ['T-35', 'correctivo', 'Cambio de ventilador o termostato, equipo comercial', 'Mano de obra', 900, 2000, false],
            ['T-36', 'refrigeracion_comercial', 'Mantenimiento de estufa / cocina industrial', 'Por unidad. Cocina industrial', 4500, 5600, true],
            ['T-37', 'refrigeracion_comercial', 'Mantenimiento de plancha industrial', 'Por unidad. Cocina industrial', 3500, 4200, true],
            ['T-38', 'refrigeracion_comercial', 'Mantenimiento de horno eléctrico / a gas', 'Por unidad. Cocina industrial', 5500, 6500, true],
            ['T-39', 'refrigeracion_comercial', 'Mantenimiento y reparación de freidora', 'Mano de obra. Cocina industrial', 4000, 4700, true],
            ['T-40', 'refrigeracion_comercial', 'Mantenimiento de campana de extracción', 'Incluye hongo y ducto superior. Cocina industrial', 7500, 8500, true],
        ];

        foreach ($filas as [$codigo, $cat, $nombre, $detalle, $precio, $hasta, $cuenta]) {
            $existe = DB::table('servicios_catalogo')->where('codigo', $codigo)->exists();
            DB::table('servicios_catalogo')->updateOrInsert(
                ['codigo' => $codigo],
                [
                    'categoria' => $cat, 'nombre' => $nombre, 'detalle' => $detalle,
                    'precio' => $precio, 'precio_hasta' => $hasta, 'cuenta_equipo' => $cuenta, 'activo' => true,
                    'updated_at' => now(),
                ] + ($existe ? [] : ['created_at' => now()])
            );
        }

        // Frases de condiciones que faltaban frente al PDF.
        $cond = DB::table('reglas_tarifario')->where('clave', 'condiciones')->first();
        if ($cond) {
            $lista = json_decode($cond->valor, true) ?: [];
            foreach ([
                'Si el equipo requiere reparación, primero se realiza la reparación y luego el mantenimiento.',
                'Después del plazo de garantía no se aceptan reclamos.',
            ] as $frase) {
                if (! in_array($frase, $lista, true)) {
                    $lista[] = $frase;
                }
            }
            DB::table('reglas_tarifario')->where('id', $cond->id)->update(['valor' => json_encode($lista, JSON_UNESCAPED_UNICODE), 'updated_at' => now()]);
        }

        // Plan Esencial (4 visitas) igual que Contrato anual de 4 visitas.
        $desc = DB::table('reglas_tarifario')->where('clave', 'descuentos')->first();
        if ($desc) {
            $lista = json_decode($desc->valor, true) ?: [];
            foreach ($lista as &$d) {
                if (($d['codigo'] ?? null) === 'PLAN_ESENCIAL') {
                    $d['min'] = 0.12;
                    $d['max'] = 0.15;
                }
            }
            unset($d);
            DB::table('reglas_tarifario')->where('id', $desc->id)->update(['valor' => json_encode($lista, JSON_UNESCAPED_UNICODE), 'updated_at' => now()]);
        }
    }
}
