<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_trabajo', function (Blueprint $t) {
            $t->id();
            $t->string('numero')->unique();
            $t->string('tipo');
            $t->string('prioridad')->default('normal');
            $t->string('estado')->default('pendiente');
            $t->foreignId('cliente_id')->constrained('clientes');
            $t->foreignId('sucursal_id')->constrained('sucursales');
            $t->foreignId('contrato_id')->nullable()->constrained('contratos')->nullOnDelete();
            $t->foreignId('ot_origen_id')->nullable()->constrained('ordenes_trabajo')->nullOnDelete();
            $t->string('origen_aviso')->nullable();
            $t->string('contacto_aviso')->nullable();
            $t->text('descripcion')->nullable();
            $t->foreignId('tecnico_id')->nullable()->constrained('tecnicos')->nullOnDelete();
            $t->date('fecha_programada')->nullable();
            $t->date('fecha_original')->nullable();
            $t->text('motivo_reprogramacion')->nullable();
            $t->timestamp('aceptada_en')->nullable();
            $t->timestamp('check_in_at')->nullable();
            $t->decimal('check_in_lat', 10, 7)->nullable();
            $t->decimal('check_in_lng', 10, 7)->nullable();
            $t->timestamp('check_out_at')->nullable();
            $t->decimal('check_out_lat', 10, 7)->nullable();
            $t->decimal('check_out_lng', 10, 7)->nullable();
            $t->text('diagnostico')->nullable();
            $t->text('causa_raiz')->nullable();
            $t->text('reparacion')->nullable();
            $t->text('motivo_devolucion')->nullable();
            $t->timestamp('cerrada_en')->nullable();
            $t->boolean('tardia')->nullable();
            $t->timestamps();
            $t->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->softDeletes();
            $t->index(['cliente_id', 'estado']);
            $t->index(['tecnico_id', 'estado']);
            $t->index('fecha_programada');
        });

        Schema::create('orden_trabajo_equipo', function (Blueprint $t) {
            $t->id();
            $t->foreignId('orden_trabajo_id')->constrained('ordenes_trabajo')->cascadeOnDelete();
            // Nulo en instalaciones hasta que la OT se cierra y se crea el equipo.
            $t->foreignId('equipo_id')->nullable()->constrained('equipos');
            $t->foreignId('tipo_equipo_id')->nullable()->constrained('tipos_equipo');
            $t->foreignId('area_id')->nullable()->constrained('areas');
            $t->jsonb('datos_previstos')->nullable();
            $t->string('estado_resultante')->nullable();
            $t->text('observaciones')->nullable();
            $t->timestamps();
            $t->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::create('historial_estados_ot', function (Blueprint $t) {
            $t->id();
            $t->foreignId('orden_trabajo_id')->constrained('ordenes_trabajo')->cascadeOnDelete();
            $t->string('estado_anterior')->nullable();
            $t->string('estado_nuevo');
            $t->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $t->text('motivo')->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index('orden_trabajo_id');
        });

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
ALTER TABLE ordenes_trabajo ADD CONSTRAINT ot_tipo_check
    CHECK (tipo IN ('preventivo','correctivo','instalacion'));
ALTER TABLE ordenes_trabajo ADD CONSTRAINT ot_estado_check
    CHECK (estado IN ('pendiente','asignada','en_proceso','en_espera','completada_tecnico','en_revision','cerrada','cancelada'));
CREATE UNIQUE INDEX ote_ot_equipo_unico ON orden_trabajo_equipo (orden_trabajo_id, equipo_id) WHERE equipo_id IS NOT NULL;
CREATE INDEX ote_equipo_idx ON orden_trabajo_equipo (equipo_id);

-- Funciones SECURITY DEFINER: leen las OT del técnico sin disparar RLS (evita recursión entre políticas).
CREATE OR REPLACE FUNCTION app_tecnico_cliente_ids() RETURNS SETOF bigint
LANGUAGE sql STABLE SECURITY DEFINER SET search_path = public AS $$
    SELECT cliente_id FROM ordenes_trabajo WHERE tecnico_id = app_tecnico_id() AND deleted_at IS NULL
$$;

CREATE OR REPLACE FUNCTION app_tecnico_sucursal_ids() RETURNS SETOF bigint
LANGUAGE sql STABLE SECURITY DEFINER SET search_path = public AS $$
    SELECT sucursal_id FROM ordenes_trabajo WHERE tecnico_id = app_tecnico_id() AND deleted_at IS NULL
$$;

CREATE OR REPLACE FUNCTION app_tecnico_equipo_ids() RETURNS SETOF bigint
LANGUAGE sql STABLE SECURITY DEFINER SET search_path = public AS $$
    SELECT ote.equipo_id FROM orden_trabajo_equipo ote
    JOIN ordenes_trabajo ot ON ot.id = ote.orden_trabajo_id
    WHERE ot.tecnico_id = app_tecnico_id() AND ot.deleted_at IS NULL AND ote.equipo_id IS NOT NULL
$$;

CREATE OR REPLACE FUNCTION app_tecnico_area_ids() RETURNS SETOF bigint
LANGUAGE sql STABLE SECURITY DEFINER SET search_path = public AS $$
    SELECT e.area_id FROM equipos e WHERE e.id IN (SELECT app_tecnico_equipo_ids())
    UNION
    SELECT ote.area_id FROM orden_trabajo_equipo ote
    JOIN ordenes_trabajo ot ON ot.id = ote.orden_trabajo_id
    WHERE ot.tecnico_id = app_tecnico_id() AND ot.deleted_at IS NULL AND ote.area_id IS NOT NULL
$$;

ALTER TABLE ordenes_trabajo ENABLE ROW LEVEL SECURITY;
ALTER TABLE orden_trabajo_equipo ENABLE ROW LEVEL SECURITY;
ALTER TABLE historial_estados_ot ENABLE ROW LEVEL SECURITY;

CREATE POLICY interno_total ON ordenes_trabajo USING (app_es_interno()) WITH CHECK (app_es_interno());
CREATE POLICY cliente_lectura ON ordenes_trabajo FOR SELECT USING (cliente_id = app_cliente_id());
CREATE POLICY tecnico_lectura ON ordenes_trabajo FOR SELECT USING (tecnico_id = app_tecnico_id());
CREATE POLICY tecnico_actualiza ON ordenes_trabajo FOR UPDATE
    USING (tecnico_id = app_tecnico_id()) WITH CHECK (tecnico_id = app_tecnico_id());

CREATE POLICY interno_total ON orden_trabajo_equipo USING (app_es_interno()) WITH CHECK (app_es_interno());
CREATE POLICY cliente_lectura ON orden_trabajo_equipo FOR SELECT
    USING (orden_trabajo_id IN (SELECT id FROM ordenes_trabajo WHERE cliente_id = app_cliente_id()));
CREATE POLICY tecnico_lectura ON orden_trabajo_equipo FOR SELECT
    USING (orden_trabajo_id IN (SELECT id FROM ordenes_trabajo WHERE tecnico_id = app_tecnico_id()));
CREATE POLICY tecnico_actualiza ON orden_trabajo_equipo FOR UPDATE
    USING (orden_trabajo_id IN (SELECT id FROM ordenes_trabajo WHERE tecnico_id = app_tecnico_id()))
    WITH CHECK (orden_trabajo_id IN (SELECT id FROM ordenes_trabajo WHERE tecnico_id = app_tecnico_id()));

CREATE POLICY interno_total ON historial_estados_ot USING (app_es_interno()) WITH CHECK (app_es_interno());
CREATE POLICY tecnico_lectura ON historial_estados_ot FOR SELECT
    USING (orden_trabajo_id IN (SELECT id FROM ordenes_trabajo WHERE tecnico_id = app_tecnico_id()));
CREATE POLICY tecnico_alta ON historial_estados_ot FOR INSERT
    WITH CHECK (orden_trabajo_id IN (SELECT id FROM ordenes_trabajo WHERE tecnico_id = app_tecnico_id()));

-- El técnico lee (solo lectura) el contexto de sus OT.
CREATE POLICY tecnico_lectura ON clientes FOR SELECT USING (id IN (SELECT app_tecnico_cliente_ids()));
CREATE POLICY tecnico_lectura ON sucursales FOR SELECT USING (id IN (SELECT app_tecnico_sucursal_ids()));
CREATE POLICY tecnico_lectura ON areas FOR SELECT USING (id IN (SELECT app_tecnico_area_ids()));
CREATE POLICY tecnico_lectura ON equipos FOR SELECT USING (id IN (SELECT app_tecnico_equipo_ids()));

DO $$ BEGIN
    IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'hvac_app') THEN
        GRANT SELECT, INSERT, UPDATE, DELETE ON ordenes_trabajo, orden_trabajo_equipo, historial_estados_ot TO hvac_app;
        GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO hvac_app;
    END IF;
END $$;
SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            foreach (['clientes', 'sucursales', 'areas', 'equipos'] as $tabla) {
                DB::unprepared("DROP POLICY IF EXISTS tecnico_lectura ON $tabla");
            }
            DB::unprepared('DROP FUNCTION IF EXISTS app_tecnico_area_ids(); DROP FUNCTION IF EXISTS app_tecnico_equipo_ids(); DROP FUNCTION IF EXISTS app_tecnico_sucursal_ids(); DROP FUNCTION IF EXISTS app_tecnico_cliente_ids();');
        }

        Schema::dropIfExists('historial_estados_ot');
        Schema::dropIfExists('orden_trabajo_equipo');
        Schema::dropIfExists('ordenes_trabajo');
    }
};
