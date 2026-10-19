<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->crear('Correctivo: reparación de falla', 'correctivo', 'correctivo', [
            ['tipo' => 'nota', 'clave' => 'falla_reportada', 'etiqueta' => 'Falla reportada / síntoma', 'obligatorio' => true],
            ['tipo' => 'nota', 'clave' => 'diagnostico', 'etiqueta' => 'Diagnóstico', 'obligatorio' => true],
            ['tipo' => 'nota', 'clave' => 'causa_raiz', 'etiqueta' => 'Causa raíz', 'obligatorio' => true],
            ['tipo' => 'nota', 'clave' => 'reparacion', 'etiqueta' => 'Reparación realizada', 'obligatorio' => true],
            ['tipo' => 'nota', 'clave' => 'repuestos', 'etiqueta' => 'Repuestos y materiales usados', 'obligatorio' => false],
            ['tipo' => 'casilla', 'clave' => 'equipo_operativo', 'etiqueta' => 'El equipo quedó operativo', 'obligatorio' => false],
            ['tipo' => 'nota', 'clave' => 'recomendaciones', 'etiqueta' => 'Recomendaciones al cliente', 'obligatorio' => false],
        ]);

        $this->crear('Diagnóstico: visita técnica', 'diagnostico', 'diagnostico', [
            ['tipo' => 'nota', 'clave' => 'hallazgos', 'etiqueta' => 'Hallazgos', 'obligatorio' => true],
            ['tipo' => 'texto', 'clave' => 'estado_equipo', 'etiqueta' => 'Estado general del equipo', 'obligatorio' => true],
            ['tipo' => 'nota', 'clave' => 'recomendaciones', 'etiqueta' => 'Recomendaciones (equipo, materiales, alcance)', 'obligatorio' => true],
            ['tipo' => 'casilla', 'clave' => 'requiere_cotizacion', 'etiqueta' => 'Requiere cotización', 'obligatorio' => false],
        ]);
    }

    public function down(): void
    {
        $ids = DB::table('plantillas_formulario')->whereIn('familia', ['correctivo', 'diagnostico'])->pluck('id');
        DB::table('plantilla_versiones')->whereIn('plantilla_id', $ids)->delete();
        DB::table('plantillas_formulario')->whereIn('id', $ids)->delete();
    }

    private function crear(string $nombre, string $familia, string $tipoTrabajo, array $campos): void
    {
        if (DB::table('plantillas_formulario')->where('familia', $familia)->exists()) {
            return;
        }

        $id = DB::table('plantillas_formulario')->insertGetId([
            'nombre' => $nombre,
            'familia' => $familia,
            'tipo_trabajo' => $tipoTrabajo,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('plantilla_versiones')->insert([
            'plantilla_id' => $id,
            'version' => 1,
            'definicion' => json_encode($campos, JSON_UNESCAPED_UNICODE),
            'publicada_en' => now(),
        ]);
    }
};
