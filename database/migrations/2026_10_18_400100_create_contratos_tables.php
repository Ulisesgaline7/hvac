<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contratos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('cliente_id')->constrained('clientes');
            $t->string('tipo');
            $t->date('vigencia_inicio');
            $t->date('vigencia_fin');
            $t->string('estado')->default('borrador')->index();
            $t->jsonb('sla')->nullable();
            $t->text('servicios_incluidos')->nullable();
            $t->text('exclusiones')->nullable();
            $t->text('garantias')->nullable();
            $t->unsignedSmallInteger('anticipacion_dias')->default(30);
            $t->text('notas')->nullable();
            $t->timestamps();
            $t->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->softDeletes();
            $t->index(['cliente_id', 'estado']);
        });

        Schema::create('contrato_equipo', function (Blueprint $t) {
            $t->id();
            $t->foreignId('contrato_id')->constrained('contratos')->cascadeOnDelete();
            $t->foreignId('equipo_id')->constrained('equipos');
            $t->string('frecuencia')->nullable();
            $t->date('fecha_inicio');
            $t->date('proxima_fecha')->nullable();
            $t->timestamps();
            $t->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->unique(['contrato_id', 'equipo_id']);
        });

        Schema::create('planes_preventivos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('contrato_id')->constrained('contratos')->cascadeOnDelete();
            $t->foreignId('tipo_equipo_id')->constrained('tipos_equipo');
            $t->string('frecuencia');
            $t->unsignedSmallInteger('anticipacion_dias')->nullable();
            $t->unsignedBigInteger('plantilla_id')->nullable(); // FK cuando exista plantillas_formulario
            $t->timestamps();
            $t->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->unique(['contrato_id', 'tipo_equipo_id']);
        });

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
ALTER TABLE contratos ADD CONSTRAINT contratos_tipo_check
    CHECK (tipo IN ('preventivo_recurrente','instalacion','correctivo','mixto'));
ALTER TABLE contratos ADD CONSTRAINT contratos_estado_check
    CHECK (estado IN ('borrador','activo','vencido','cancelado'));
ALTER TABLE contratos ADD CONSTRAINT contratos_vigencia_check CHECK (vigencia_fin >= vigencia_inicio);
ALTER TABLE contrato_equipo ADD CONSTRAINT contrato_equipo_frecuencia_check
    CHECK (frecuencia IS NULL OR frecuencia IN ('mensual','trimestral','semestral','anual'));
ALTER TABLE planes_preventivos ADD CONSTRAINT planes_frecuencia_check
    CHECK (frecuencia IN ('mensual','trimestral','semestral','anual'));

ALTER TABLE contratos ENABLE ROW LEVEL SECURITY;
ALTER TABLE contrato_equipo ENABLE ROW LEVEL SECURITY;
ALTER TABLE planes_preventivos ENABLE ROW LEVEL SECURITY;

CREATE POLICY interno_total ON contratos USING (app_es_interno()) WITH CHECK (app_es_interno());
CREATE POLICY cliente_lectura ON contratos FOR SELECT USING (cliente_id = app_cliente_id());

CREATE POLICY interno_total ON contrato_equipo USING (app_es_interno()) WITH CHECK (app_es_interno());
CREATE POLICY cliente_lectura ON contrato_equipo FOR SELECT
    USING (contrato_id IN (SELECT id FROM contratos WHERE cliente_id = app_cliente_id()));

CREATE POLICY interno_total ON planes_preventivos USING (app_es_interno()) WITH CHECK (app_es_interno());

DO $$ BEGIN
    IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'hvac_app') THEN
        GRANT SELECT, INSERT, UPDATE, DELETE ON contratos, contrato_equipo, planes_preventivos TO hvac_app;
        GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO hvac_app;
    END IF;
END $$;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('planes_preventivos');
        Schema::dropIfExists('contrato_equipo');
        Schema::dropIfExists('contratos');
    }
};
