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

        DB::unprepared('DROP FUNCTION IF EXISTS public.app_verificar_informe(text)');
        DB::unprepared(<<<'SQL'
CREATE FUNCTION public.app_verificar_informe(p_token text)
 RETURNS TABLE(numero text, publicado_en timestamp without time zone, cliente text, hash_pdf text, ruta_pdf text)
 LANGUAGE sql
 STABLE SECURITY DEFINER
 SET search_path TO 'public'
AS $function$
    SELECT o.numero::text, i.publicado_en::timestamp, c.razon_social::text, i.hash_pdf::text, i.ruta_pdf::text
    FROM informes i
    JOIN ordenes_trabajo o ON o.id = i.orden_trabajo_id
    JOIN clientes c ON c.id = o.cliente_id
    WHERE i.token_verificacion = p_token AND i.estado = 'publicado' AND o.deleted_at IS NULL
$function$
SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared('DROP FUNCTION IF EXISTS public.app_verificar_informe(text)');
        DB::unprepared(<<<'SQL'
CREATE FUNCTION public.app_verificar_informe(p_token text)
 RETURNS TABLE(numero text, publicado_en timestamp without time zone, cliente text)
 LANGUAGE sql
 STABLE SECURITY DEFINER
 SET search_path TO 'public'
AS $function$
    SELECT o.numero::text, i.publicado_en::timestamp, c.razon_social::text
    FROM informes i
    JOIN ordenes_trabajo o ON o.id = i.orden_trabajo_id
    JOIN clientes c ON c.id = o.cliente_id
    WHERE i.token_verificacion = p_token AND i.estado = 'publicado' AND o.deleted_at IS NULL
$function$
SQL);
    }
};
