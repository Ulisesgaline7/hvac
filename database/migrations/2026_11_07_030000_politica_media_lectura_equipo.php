<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Lectura de documentos escaneados de equipos: hereda el filtro RLS de la tabla equipos (cliente dueño, técnico asignado, interno). */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared('DROP POLICY IF EXISTS media_lectura_equipo ON media');
        DB::unprepared(<<<'SQL'
CREATE POLICY media_lectura_equipo ON media FOR SELECT USING (
    (model_type)::text = 'App\Models\Equipo'
    AND EXISTS (SELECT 1 FROM equipos e WHERE e.id = media.model_id)
)
SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::unprepared('DROP POLICY IF EXISTS media_lectura_equipo ON media');
        }
    }
};
