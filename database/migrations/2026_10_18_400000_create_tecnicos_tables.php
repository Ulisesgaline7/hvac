<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tecnicos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $t->string('telefono')->nullable();
            $t->string('zona')->nullable();
            $t->jsonb('especialidades')->nullable();
            $t->string('estado_homologacion')->default('pendiente')->index();
            $t->text('notas')->nullable();
            $t->timestamps();
            $t->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->softDeletes();
        });

        Schema::create('documentos_tecnico', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tecnico_id')->constrained('tecnicos')->cascadeOnDelete();
            $t->string('tipo');
            $t->date('vencimiento')->nullable()->index();
            $t->text('notas')->nullable();
            $t->timestamps();
            $t->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->softDeletes();
        });

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
ALTER TABLE tecnicos ADD CONSTRAINT tecnicos_homologacion_check
    CHECK (estado_homologacion IN ('pendiente','aprobado','suspendido'));

CREATE OR REPLACE FUNCTION app_user_id() RETURNS bigint
LANGUAGE sql STABLE AS $$ SELECT nullif(current_setting('app.user_id', true), '')::bigint $$;

CREATE OR REPLACE FUNCTION app_tecnico_id() RETURNS bigint
LANGUAGE sql STABLE SECURITY DEFINER SET search_path = public AS $$
    SELECT id FROM tecnicos
    WHERE user_id = app_user_id() AND estado_homologacion = 'aprobado' AND deleted_at IS NULL
$$;

ALTER TABLE tecnicos ENABLE ROW LEVEL SECURITY;
ALTER TABLE documentos_tecnico ENABLE ROW LEVEL SECURITY;

CREATE POLICY interno_total ON tecnicos USING (app_es_interno()) WITH CHECK (app_es_interno());
CREATE POLICY propio_lectura ON tecnicos FOR SELECT USING (user_id = app_user_id());

CREATE POLICY interno_total ON documentos_tecnico USING (app_es_interno()) WITH CHECK (app_es_interno());
CREATE POLICY propio_lectura ON documentos_tecnico FOR SELECT
    USING (tecnico_id IN (SELECT id FROM tecnicos WHERE user_id = app_user_id()));
CREATE POLICY propio_alta ON documentos_tecnico FOR INSERT
    WITH CHECK (tecnico_id IN (SELECT id FROM tecnicos WHERE user_id = app_user_id()));

DO $$ BEGIN
    IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'hvac_app') THEN
        GRANT SELECT, INSERT, UPDATE, DELETE ON tecnicos, documentos_tecnico TO hvac_app;
        GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO hvac_app;
    END IF;
END $$;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_tecnico');
        Schema::dropIfExists('tecnicos');

        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared('DROP FUNCTION IF EXISTS app_tecnico_id(); DROP FUNCTION IF EXISTS app_user_id();');
        }
    }
};
