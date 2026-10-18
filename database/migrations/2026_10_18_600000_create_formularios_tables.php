<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plantillas_formulario', function (Blueprint $t) {
            $t->id();
            $t->string('nombre')->unique();
            $t->string('familia');
            $t->text('descripcion')->nullable();
            $t->timestamps();
            $t->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->softDeletes();
        });

        Schema::create('plantilla_versiones', function (Blueprint $t) {
            $t->id();
            $t->foreignId('plantilla_id')->constrained('plantillas_formulario')->cascadeOnDelete();
            $t->unsignedSmallInteger('version');
            $t->jsonb('definicion');
            $t->timestamp('publicada_en')->useCurrent();
            $t->foreignId('publicada_por')->nullable()->constrained('users')->nullOnDelete();
            $t->unique(['plantilla_id', 'version']);
        });

        Schema::create('fichas_tecnicas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('orden_trabajo_id')->constrained('ordenes_trabajo')->cascadeOnDelete();
            $t->foreignId('orden_trabajo_equipo_id')->unique()->constrained('orden_trabajo_equipo')->cascadeOnDelete();
            $t->foreignId('plantilla_version_id')->constrained('plantilla_versiones');
            $t->jsonb('respuestas')->nullable();
            $t->boolean('completa')->default(false);
            $t->jsonb('alertas')->nullable();
            $t->timestamps();
            $t->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->index('orden_trabajo_id');
        });

        Schema::table('tipos_equipo', fn (Blueprint $t) => $t->foreign('plantilla_id')->references('id')->on('plantillas_formulario')->nullOnDelete());
        Schema::table('planes_preventivos', fn (Blueprint $t) => $t->foreign('plantilla_id')->references('id')->on('plantillas_formulario')->nullOnDelete());

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
ALTER TABLE plantillas_formulario ENABLE ROW LEVEL SECURITY;
ALTER TABLE plantilla_versiones ENABLE ROW LEVEL SECURITY;
ALTER TABLE fichas_tecnicas ENABLE ROW LEVEL SECURITY;

CREATE POLICY interno_total ON plantillas_formulario USING (app_es_interno()) WITH CHECK (app_es_interno());
CREATE POLICY lectura_con_contexto ON plantillas_formulario FOR SELECT
    USING (app_tecnico_id() IS NOT NULL OR app_cliente_id() IS NOT NULL);

CREATE POLICY interno_total ON plantilla_versiones USING (app_es_interno()) WITH CHECK (app_es_interno());
CREATE POLICY lectura_con_contexto ON plantilla_versiones FOR SELECT
    USING (app_tecnico_id() IS NOT NULL OR app_cliente_id() IS NOT NULL);

CREATE POLICY interno_total ON fichas_tecnicas USING (app_es_interno()) WITH CHECK (app_es_interno());
CREATE POLICY cliente_lectura ON fichas_tecnicas FOR SELECT
    USING (orden_trabajo_id IN (SELECT id FROM ordenes_trabajo WHERE cliente_id = app_cliente_id() AND estado = 'cerrada'));
CREATE POLICY tecnico_lectura ON fichas_tecnicas FOR SELECT
    USING (orden_trabajo_id IN (SELECT id FROM ordenes_trabajo WHERE tecnico_id = app_tecnico_id()));
CREATE POLICY tecnico_alta ON fichas_tecnicas FOR INSERT
    WITH CHECK (orden_trabajo_id IN (SELECT id FROM ordenes_trabajo WHERE tecnico_id = app_tecnico_id()));
CREATE POLICY tecnico_actualiza ON fichas_tecnicas FOR UPDATE
    USING (orden_trabajo_id IN (SELECT id FROM ordenes_trabajo WHERE tecnico_id = app_tecnico_id()))
    WITH CHECK (orden_trabajo_id IN (SELECT id FROM ordenes_trabajo WHERE tecnico_id = app_tecnico_id()));

DO $$ BEGIN
    IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'hvac_app') THEN
        GRANT SELECT, INSERT, UPDATE, DELETE ON plantillas_formulario, plantilla_versiones, fichas_tecnicas TO hvac_app;
        GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO hvac_app;
    END IF;
END $$;
SQL);
    }

    public function down(): void
    {
        Schema::table('planes_preventivos', fn (Blueprint $t) => $t->dropForeign(['plantilla_id']));
        Schema::table('tipos_equipo', fn (Blueprint $t) => $t->dropForeign(['plantilla_id']));
        Schema::dropIfExists('fichas_tecnicas');
        Schema::dropIfExists('plantilla_versiones');
        Schema::dropIfExists('plantillas_formulario');
    }
};
