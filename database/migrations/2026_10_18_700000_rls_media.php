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
        // La subconsulta hereda el RLS de orden_trabajo_equipo: quien no ve la línea, no ve sus archivos.
        $visible = "model_type = {$tipo} AND EXISTS (SELECT 1 FROM orden_trabajo_equipo o WHERE o.id = media.model_id)";
        $noCliente = "coalesce(current_setting('app.cliente_id', true), '') = ''";

        DB::unprepared(<<<SQL
ALTER TABLE media ENABLE ROW LEVEL SECURITY;

CREATE POLICY media_interno ON media FOR ALL
    USING (current_setting('app.es_interno', true) = 'true')
    WITH CHECK (current_setting('app.es_interno', true) = 'true');

CREATE POLICY media_lectura ON media FOR SELECT USING ({$visible});

CREATE POLICY media_alta ON media FOR INSERT WITH CHECK ({$noCliente} AND {$visible});

CREATE POLICY media_actualiza ON media FOR UPDATE USING ({$noCliente} AND {$visible}) WITH CHECK ({$noCliente} AND {$visible});
SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
DROP POLICY IF EXISTS media_actualiza ON media;
DROP POLICY IF EXISTS media_alta ON media;
DROP POLICY IF EXISTS media_lectura ON media;
DROP POLICY IF EXISTS media_interno ON media;
ALTER TABLE media DISABLE ROW LEVEL SECURITY;
SQL);
    }
};
