<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plantillas_contrato', function (Blueprint $t) {
            $t->id();
            $t->string('clave', 40)->unique();
            $t->string('nombre');
            $t->string('tipo', 40);
            $t->unsignedSmallInteger('visitas_anuales')->nullable();
            $t->string('periodicidad', 20)->nullable();
            $t->text('servicios_incluidos')->nullable();
            $t->text('exclusiones')->nullable();
            $t->text('garantias')->nullable();
            $t->jsonb('sla')->nullable();
            $t->boolean('activa')->default(true);
            $t->timestamps();
        });

        $excl = "Repuestos y materiales: se cotizan aparte.\nReparaciones correctivas: si el equipo requiere reparación, primero se repara y luego se hace el mantenimiento.\nDaños por mal uso, golpes o variaciones de voltaje.";
        $gar = "30 días sobre la mano de obra de cada visita: el técnico que hizo el trabajo corrige sin costo fallas por limpieza o revisión mal hechas.\nPiezas gastadas, falta de gas y mal uso no están cubiertos.\nRepuestos: garantía del fabricante.\nDespués del plazo de garantía no se aceptan reclamos.";

        $planes = [
            ['PLAN_BASICO', 'Plan Básico', 2, 'Cada 6 meses', 'semestral'],
            ['PLAN_ESENCIAL', 'Plan Esencial', 4, 'Cada 3 meses', 'trimestral'],
            ['PLAN_EMPRESARIAL', 'Plan Empresarial', 6, 'Cada 2 meses', 'bimestral'],
            ['PLAN_PREMIUM', 'Plan Premium', 12, 'Mensual', 'mensual'],
        ];
        $now = now();

        foreach ($planes as [$clave, $nombre, $visitas, $frec, $per]) {
            DB::table('plantillas_contrato')->insert([
                'clave' => $clave, 'nombre' => $nombre, 'tipo' => 'preventivo_recurrente',
                'visitas_anuales' => $visitas, 'periodicidad' => $per,
                'servicios_incluidos' => "{$nombre}: {$visitas} visitas de mantenimiento preventivo al año ({$frec}) a cada equipo listado en este contrato.\nCada visita incluye limpieza, revisión del equipo e informe de la visita.",
                'exclusiones' => $excl, 'garantias' => $gar,
                'sla' => json_encode(['visitas_anuales' => (string) $visitas, 'frecuencia' => $frec], JSON_UNESCAPED_UNICODE),
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        DB::table('plantillas_contrato')->insert([
            'clave' => 'SERVICIO_UNICO', 'nombre' => 'Servicio único (una sola visita)', 'tipo' => 'mixto',
            'visitas_anuales' => 1, 'periodicidad' => 'unica',
            'servicios_incluidos' => "Servicio descrito en la cotización aceptada, en una sola visita.\nAplica a instalación, correctivo, refrigeración comercial, administración y preventivo de una visita.",
            'exclusiones' => $excl,
            'garantias' => "Mano de obra: 90 días en instalaciones y 30 días en los demás servicios, corregidos sin costo por el técnico que hizo el trabajo.\nRepuestos: garantía del fabricante.\nDespués del plazo de garantía no se aceptan reclamos.",
            'sla' => json_encode(['visitas' => '1'], JSON_UNESCAPED_UNICODE),
            'created_at' => $now, 'updated_at' => $now,
        ]);

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $rol = config('database.connections.pgsql_app.username') ?: 'usuario_app';
        $existe = DB::selectOne('select 1 as ok from pg_roles where rolname = ?', [$rol]);

        DB::statement('ALTER TABLE plantillas_contrato ENABLE ROW LEVEL SECURITY');
        DB::statement('CREATE POLICY interno_total ON plantillas_contrato USING (app_es_interno()) WITH CHECK (app_es_interno())');
        if ($existe) {
            DB::statement("GRANT SELECT, INSERT, UPDATE, DELETE ON plantillas_contrato TO \"{$rol}\"");
            DB::statement("GRANT USAGE, SELECT ON SEQUENCE plantillas_contrato_id_seq TO \"{$rol}\"");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('plantillas_contrato');
    }
};
