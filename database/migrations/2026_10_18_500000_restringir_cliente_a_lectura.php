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
DROP POLICY aislamiento ON clientes;
DROP POLICY aislamiento ON sucursales;
DROP POLICY aislamiento ON areas;
DROP POLICY aislamiento ON equipos;

CREATE POLICY interno_total ON clientes USING (app_es_interno()) WITH CHECK (app_es_interno());
CREATE POLICY cliente_lectura ON clientes FOR SELECT USING (id = app_cliente_id());

CREATE POLICY interno_total ON sucursales USING (app_es_interno()) WITH CHECK (app_es_interno());
CREATE POLICY cliente_lectura ON sucursales FOR SELECT USING (cliente_id = app_cliente_id());

CREATE POLICY interno_total ON areas USING (app_es_interno()) WITH CHECK (app_es_interno());
CREATE POLICY cliente_lectura ON areas FOR SELECT
    USING (sucursal_id IN (SELECT id FROM sucursales WHERE cliente_id = app_cliente_id()));

CREATE POLICY interno_total ON equipos USING (app_es_interno()) WITH CHECK (app_es_interno());
CREATE POLICY cliente_lectura ON equipos FOR SELECT
    USING (area_id IN (SELECT a.id FROM areas a JOIN sucursales s ON s.id = a.sucursal_id WHERE s.cliente_id = app_cliente_id()));
SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (['clientes', 'sucursales', 'areas', 'equipos'] as $t) {
            DB::unprepared("DROP POLICY IF EXISTS interno_total ON $t; DROP POLICY IF EXISTS cliente_lectura ON $t;");
        }

        DB::unprepared(<<<'SQL'
CREATE POLICY aislamiento ON clientes
    USING (app_es_interno() OR id = app_cliente_id()) WITH CHECK (app_es_interno() OR id = app_cliente_id());
CREATE POLICY aislamiento ON sucursales
    USING (app_es_interno() OR cliente_id = app_cliente_id()) WITH CHECK (app_es_interno() OR cliente_id = app_cliente_id());
CREATE POLICY aislamiento ON areas
    USING (app_es_interno() OR sucursal_id IN (SELECT id FROM sucursales WHERE cliente_id = app_cliente_id()))
    WITH CHECK (app_es_interno() OR sucursal_id IN (SELECT id FROM sucursales WHERE cliente_id = app_cliente_id()));
CREATE POLICY aislamiento ON equipos
    USING (app_es_interno() OR area_id IN (SELECT a.id FROM areas a JOIN sucursales s ON s.id = a.sucursal_id WHERE s.cliente_id = app_cliente_id()))
    WITH CHECK (app_es_interno() OR area_id IN (SELECT a.id FROM areas a JOIN sucursales s ON s.id = a.sucursal_id WHERE s.cliente_id = app_cliente_id()));
SQL);
    }
};
