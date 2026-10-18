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

        $tipo = "'" . \App\Models\OrdenTrabajoEquipo::class . "'";

        DB::unprepared(<<<SQL
DROP POLICY IF EXISTS media_lectura ON media;
CREATE POLICY media_lectura ON media FOR SELECT USING (
    model_type = {$tipo} AND EXISTS (
        SELECT 1 FROM orden_trabajo_equipo o
        JOIN ordenes_trabajo t ON t.id = o.orden_trabajo_id
        WHERE o.id = media.model_id
          AND (coalesce(current_setting('app.cliente_id', true), '') = '' OR t.estado = 'cerrada')
    )
);

DROP POLICY IF EXISTS cliente_lectura ON actas_conformidad;
CREATE POLICY cliente_lectura ON actas_conformidad FOR SELECT USING (
    orden_trabajo_id IN (SELECT id FROM ordenes_trabajo WHERE cliente_id = app_cliente_id() AND estado = 'cerrada')
);

DROP POLICY IF EXISTS cliente_lectura ON trabajos_adicionales;
CREATE POLICY cliente_lectura ON trabajos_adicionales FOR SELECT USING (
    orden_trabajo_id IN (SELECT id FROM ordenes_trabajo WHERE cliente_id = app_cliente_id() AND estado = 'cerrada')
);
SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $tipo = "'" . \App\Models\OrdenTrabajoEquipo::class . "'";

        DB::unprepared(<<<SQL
DROP POLICY IF EXISTS media_lectura ON media;
CREATE POLICY media_lectura ON media FOR SELECT USING (
    model_type = {$tipo} AND EXISTS (SELECT 1 FROM orden_trabajo_equipo o WHERE o.id = media.model_id)
);

DROP POLICY IF EXISTS cliente_lectura ON actas_conformidad;
CREATE POLICY cliente_lectura ON actas_conformidad FOR SELECT USING (
    orden_trabajo_id IN (SELECT id FROM ordenes_trabajo WHERE cliente_id = app_cliente_id())
);

DROP POLICY IF EXISTS cliente_lectura ON trabajos_adicionales;
CREATE POLICY cliente_lectura ON trabajos_adicionales FOR SELECT USING (
    orden_trabajo_id IN (SELECT id FROM ordenes_trabajo WHERE cliente_id = app_cliente_id())
);
SQL);
    }
};
