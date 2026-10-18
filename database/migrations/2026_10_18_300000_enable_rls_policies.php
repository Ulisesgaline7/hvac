<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION app_es_interno() RETURNS boolean
LANGUAGE sql STABLE AS $$ SELECT coalesce(current_setting('app.es_interno', true), '') = 'true' $$;

CREATE OR REPLACE FUNCTION app_cliente_id() RETURNS bigint
LANGUAGE sql STABLE AS $$ SELECT nullif(current_setting('app.cliente_id', true), '')::bigint $$;

ALTER TABLE clientes ENABLE ROW LEVEL SECURITY;
ALTER TABLE sucursales ENABLE ROW LEVEL SECURITY;
ALTER TABLE areas ENABLE ROW LEVEL SECURITY;
ALTER TABLE equipos ENABLE ROW LEVEL SECURITY;

CREATE POLICY aislamiento ON clientes
    USING (app_es_interno() OR id = app_cliente_id())
    WITH CHECK (app_es_interno() OR id = app_cliente_id());

CREATE POLICY aislamiento ON sucursales
    USING (app_es_interno() OR cliente_id = app_cliente_id())
    WITH CHECK (app_es_interno() OR cliente_id = app_cliente_id());

CREATE POLICY aislamiento ON areas
    USING (app_es_interno() OR sucursal_id IN (SELECT id FROM sucursales WHERE cliente_id = app_cliente_id()))
    WITH CHECK (app_es_interno() OR sucursal_id IN (SELECT id FROM sucursales WHERE cliente_id = app_cliente_id()));

CREATE POLICY aislamiento ON equipos
    USING (app_es_interno() OR area_id IN (
        SELECT a.id FROM areas a JOIN sucursales s ON s.id = a.sucursal_id WHERE s.cliente_id = app_cliente_id()))
    WITH CHECK (app_es_interno() OR area_id IN (
        SELECT a.id FROM areas a JOIN sucursales s ON s.id = a.sucursal_id WHERE s.cliente_id = app_cliente_id()));
SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (['equipos', 'areas', 'sucursales', 'clientes'] as $tabla) {
            DB::unprepared("DROP POLICY IF EXISTS aislamiento ON $tabla; ALTER TABLE $tabla DISABLE ROW LEVEL SECURITY;");
        }
        DB::unprepared('DROP FUNCTION IF EXISTS app_es_interno(); DROP FUNCTION IF EXISTS app_cliente_id();');
    }
};
