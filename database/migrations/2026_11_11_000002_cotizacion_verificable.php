<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('cotizaciones', 'token_verificacion')) {
            Schema::table('cotizaciones', function (Blueprint $t) {
                $t->string('token_verificacion', 64)->nullable()->unique();
                $t->string('ruta_pdf')->nullable();
                $t->string('hash_pdf', 64)->nullable();
                $t->string('pdf_estado', 20)->nullable();
                $t->timestamp('emitida_en')->nullable();
            });
        }

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared('DROP FUNCTION IF EXISTS public.app_verificar_cotizacion(text)');
        DB::unprepared(<<<'SQL'
CREATE FUNCTION public.app_verificar_cotizacion(p_token text)
 RETURNS TABLE(numero text, cliente text, fecha date, valida_hasta date, total numeric, estado text, ruta_pdf text, hash_pdf text, emitida_en timestamp without time zone)
 LANGUAGE sql
 STABLE SECURITY DEFINER
 SET search_path TO 'public'
AS $function$
    SELECT q.numero::text, c.razon_social::text, q.fecha::date, q.valida_hasta::date, q.total::numeric, q.estado::text, q.ruta_pdf::text, q.hash_pdf::text, q.emitida_en::timestamp
    FROM cotizaciones q
    JOIN clientes c ON c.id = q.cliente_id
    WHERE q.token_verificacion = p_token AND q.ruta_pdf IS NOT NULL AND q.deleted_at IS NULL
$function$
SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared('DROP FUNCTION IF EXISTS public.app_verificar_cotizacion(text)');
        }
        Schema::table('cotizaciones', function (Blueprint $t) {
            $t->dropColumn(['token_verificacion', 'ruta_pdf', 'hash_pdf', 'pdf_estado', 'emitida_en']);
        });
    }
};
