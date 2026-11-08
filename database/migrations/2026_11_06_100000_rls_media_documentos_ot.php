<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tipo = "model_type = 'App\\Models\\OrdenTrabajo' AND collection_name = 'documentos_ot'";

        DB::unprepared("
            CREATE POLICY media_ot_alta ON media FOR INSERT
            WITH CHECK ($tipo AND model_id IN (SELECT app_tecnico_ot_ids()));

            CREATE POLICY media_ot_actualiza ON media FOR UPDATE
            USING ($tipo AND model_id IN (SELECT app_tecnico_ot_ids()))
            WITH CHECK ($tipo AND model_id IN (SELECT app_tecnico_ot_ids()));

            CREATE POLICY media_ot_lectura_tecnico ON media FOR SELECT
            USING ($tipo AND model_id IN (SELECT app_tecnico_ot_ids()));

            CREATE POLICY media_ot_lectura_cliente ON media FOR SELECT
            USING (
                $tipo
                AND coalesce(current_setting('app.cliente_id', true), '') <> ''
                AND EXISTS (
                    SELECT 1 FROM ordenes_trabajo ot
                    WHERE ot.id = media.model_id
                      AND ot.estado = 'cerrada'
                      AND ot.cliente_id = app_cliente_id()
                )
            );
        ");
    }

    public function down(): void
    {
        DB::unprepared('
            DROP POLICY IF EXISTS media_ot_alta ON media;
            DROP POLICY IF EXISTS media_ot_actualiza ON media;
            DROP POLICY IF EXISTS media_ot_lectura_tecnico ON media;
            DROP POLICY IF EXISTS media_ot_lectura_cliente ON media;
        ');
    }
};
