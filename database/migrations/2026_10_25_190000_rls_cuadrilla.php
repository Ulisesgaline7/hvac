<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Políticas SELECT de los hijos de la OT que pasan a incluir a toda la cuadrilla. */
    private const LECTURA = [
        'actas_conformidad', 'fichas_tecnicas', 'garantias', 'historial_estados_ot', 'informes',
        'movimientos_refrigerante', 'orden_trabajo_equipo', 'trabajos_adicionales',
    ];

    /** Tablas donde cualquier miembro puede crear y modificar (el líder ya podía). */
    private const ALTA_Y_ACTUALIZA = ['actas_conformidad', 'fichas_tecnicas', 'movimientos_refrigerante', 'trabajos_adicionales'];

    private function condicion(bool $cuadrilla): string
    {
        return $cuadrilla
            ? 'orden_trabajo_id IN (SELECT app_tecnico_ot_ids())'
            : 'orden_trabajo_id IN (SELECT id FROM ordenes_trabajo WHERE tecnico_id = app_tecnico_id())';
    }

    private function aplicarPoliticas(bool $cuadrilla): void
    {
        $cond = $this->condicion($cuadrilla);

        foreach (self::LECTURA as $t) {
            DB::unprepared("DROP POLICY IF EXISTS tecnico_lectura ON {$t}; CREATE POLICY tecnico_lectura ON {$t} FOR SELECT USING ({$cond});");
        }

        foreach (self::ALTA_Y_ACTUALIZA as $t) {
            DB::unprepared("DROP POLICY IF EXISTS tecnico_alta ON {$t}; CREATE POLICY tecnico_alta ON {$t} FOR INSERT WITH CHECK ({$cond});");
            DB::unprepared("DROP POLICY IF EXISTS tecnico_actualiza ON {$t}; CREATE POLICY tecnico_actualiza ON {$t} FOR UPDATE USING ({$cond}) WITH CHECK ({$cond});");
        }

        DB::unprepared("DROP POLICY IF EXISTS tecnico_actualiza ON orden_trabajo_equipo; CREATE POLICY tecnico_actualiza ON orden_trabajo_equipo FOR UPDATE USING ({$cond}) WITH CHECK ({$cond});");

        $ot = $cuadrilla ? 'id IN (SELECT app_tecnico_ot_ids())' : 'tecnico_id = app_tecnico_id()';
        DB::unprepared("DROP POLICY IF EXISTS tecnico_lectura ON ordenes_trabajo; CREATE POLICY tecnico_lectura ON ordenes_trabajo FOR SELECT USING ({$ot});");
    }

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
-- OT donde el técnico es líder (ordenes_trabajo.tecnico_id) o miembro de la cuadrilla.
CREATE OR REPLACE FUNCTION app_tecnico_ot_ids() RETURNS SETOF bigint
LANGUAGE sql STABLE SECURITY DEFINER SET search_path = public AS $$
    SELECT id FROM ordenes_trabajo WHERE tecnico_id = app_tecnico_id()
    UNION
    SELECT orden_trabajo_id FROM orden_trabajo_tecnico WHERE tecnico_id = app_tecnico_id()
$$;

CREATE OR REPLACE FUNCTION app_tecnico_cliente_ids() RETURNS SETOF bigint
LANGUAGE sql STABLE SECURITY DEFINER SET search_path = public AS $$
    SELECT cliente_id FROM ordenes_trabajo WHERE id IN (SELECT app_tecnico_ot_ids()) AND deleted_at IS NULL
$$;

CREATE OR REPLACE FUNCTION app_tecnico_sucursal_ids() RETURNS SETOF bigint
LANGUAGE sql STABLE SECURITY DEFINER SET search_path = public AS $$
    SELECT sucursal_id FROM ordenes_trabajo WHERE id IN (SELECT app_tecnico_ot_ids()) AND deleted_at IS NULL
$$;

CREATE OR REPLACE FUNCTION app_tecnico_equipo_ids() RETURNS SETOF bigint
LANGUAGE sql STABLE SECURITY DEFINER SET search_path = public AS $$
    SELECT ote.equipo_id FROM orden_trabajo_equipo ote
    JOIN ordenes_trabajo ot ON ot.id = ote.orden_trabajo_id
    WHERE ot.id IN (SELECT app_tecnico_ot_ids()) AND ot.deleted_at IS NULL AND ote.equipo_id IS NOT NULL
$$;
SQL);

        // app_tecnico_area_ids() se apoya en app_tecnico_equipo_ids() y en las líneas de OT; se redefine igual.
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION app_tecnico_area_ids() RETURNS SETOF bigint
LANGUAGE sql STABLE SECURITY DEFINER SET search_path = public AS $$
    SELECT e.area_id FROM equipos e WHERE e.id IN (SELECT app_tecnico_equipo_ids())
    UNION
    SELECT ote.area_id FROM orden_trabajo_equipo ote
    JOIN ordenes_trabajo ot ON ot.id = ote.orden_trabajo_id
    WHERE ot.id IN (SELECT app_tecnico_ot_ids()) AND ot.deleted_at IS NULL AND ote.area_id IS NOT NULL
$$;
SQL);

        $this->aplicarPoliticas(true);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $this->aplicarPoliticas(false);

        DB::unprepared(<<<'SQL'
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

DROP FUNCTION IF EXISTS app_tecnico_ot_ids();
SQL);
    }
};
