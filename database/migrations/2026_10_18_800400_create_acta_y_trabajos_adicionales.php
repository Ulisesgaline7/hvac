<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tablas = ['actas_conformidad', 'trabajos_adicionales'];

    public function up(): void
    {
        Schema::create('actas_conformidad', function (Blueprint $t) {
            $t->id();
            $t->foreignId('orden_trabajo_id')->constrained('ordenes_trabajo')->cascadeOnDelete();
            $t->date('fecha')->nullable();
            $t->jsonb('confirmaciones')->nullable();
            $t->text('observaciones_cliente')->nullable();
            $t->unsignedSmallInteger('calificacion')->nullable();
            $t->string('recibe_nombre')->nullable();
            $t->string('recibe_cargo')->nullable();
            $t->timestamps();
            $t->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->index('orden_trabajo_id');
        });

        Schema::create('trabajos_adicionales', function (Blueprint $t) {
            $t->id();
            $t->foreignId('orden_trabajo_id')->constrained('ordenes_trabajo')->cascadeOnDelete();
            $t->foreignId('orden_trabajo_equipo_id')->nullable()->constrained('orden_trabajo_equipo')->nullOnDelete();
            $t->date('fecha')->nullable();
            $t->text('hallazgo')->nullable();
            $t->text('trabajo_propuesto')->nullable();
            $t->text('consecuencias')->nullable();
            $t->jsonb('detalle_costo')->nullable();
            $t->decimal('subtotal', 12, 2)->default(0);
            $t->decimal('isv', 12, 2)->default(0);
            $t->decimal('total', 12, 2)->default(0);
            $t->string('tiempo_adicional')->nullable();
            $t->date('nueva_fecha')->nullable();
            $t->string('decision')->default('pendiente');
            $t->string('decidido_por_nombre')->nullable();
            $t->string('decidido_por_cargo')->nullable();
            $t->timestamp('decidido_en')->nullable();
            $t->timestamps();
            $t->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->index('orden_trabajo_id');
        });

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
ALTER TABLE actas_conformidad ADD CONSTRAINT acta_calificacion_check CHECK (calificacion IS NULL OR calificacion BETWEEN 1 AND 5);
ALTER TABLE trabajos_adicionales ADD CONSTRAINT ta_decision_check CHECK (decision IN ('pendiente','aprobado','rechazado'));
SQL);

        foreach ($this->tablas as $tabla) {
            DB::unprepared(<<<SQL
ALTER TABLE {$tabla} ENABLE ROW LEVEL SECURITY;

CREATE POLICY interno_total ON {$tabla} USING (app_es_interno()) WITH CHECK (app_es_interno());
CREATE POLICY cliente_lectura ON {$tabla} FOR SELECT
    USING (orden_trabajo_id IN (SELECT id FROM ordenes_trabajo WHERE cliente_id = app_cliente_id()));
CREATE POLICY tecnico_lectura ON {$tabla} FOR SELECT
    USING (orden_trabajo_id IN (SELECT id FROM ordenes_trabajo WHERE tecnico_id = app_tecnico_id()));
CREATE POLICY tecnico_alta ON {$tabla} FOR INSERT
    WITH CHECK (orden_trabajo_id IN (SELECT id FROM ordenes_trabajo WHERE tecnico_id = app_tecnico_id()));
CREATE POLICY tecnico_actualiza ON {$tabla} FOR UPDATE
    USING (orden_trabajo_id IN (SELECT id FROM ordenes_trabajo WHERE tecnico_id = app_tecnico_id()))
    WITH CHECK (orden_trabajo_id IN (SELECT id FROM ordenes_trabajo WHERE tecnico_id = app_tecnico_id()));
SQL);
        }

        if (DB::table('pg_roles')->where('rolname', 'hvac_app')->exists()) {
            DB::unprepared('GRANT SELECT, INSERT, UPDATE, DELETE ON actas_conformidad, trabajos_adicionales TO hvac_app; GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO hvac_app;');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('trabajos_adicionales');
        Schema::dropIfExists('actas_conformidad');
    }
};
