<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('cotizaciones', 'token_aceptacion')) {
            Schema::table('cotizaciones', function (Blueprint $t) {
                $t->string('token_aceptacion', 64)->nullable()->unique();
                $t->string('aprobada_por_cargo')->nullable();
                $t->string('aceptada_ip', 45)->nullable();
                $t->string('aceptada_user_agent', 500)->nullable();
                $t->string('hash_documento', 64)->nullable();
            });
        }

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION cotizacion_id_por_token(p text) RETURNS bigint
LANGUAGE sql STABLE SECURITY DEFINER SET search_path = public AS $$
    SELECT id FROM cotizaciones
    WHERE token_aceptacion = p AND deleted_at IS NULL AND estado = 'enviada'
      AND (valida_hasta IS NULL OR valida_hasta >= current_date)
$$;
SQL);
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::unprepared('DROP FUNCTION IF EXISTS cotizacion_id_por_token(text)');
        }
        Schema::table('cotizaciones', fn (Blueprint $t) => $t->dropColumn([
            'token_aceptacion', 'aprobada_por_cargo', 'aceptada_ip', 'aceptada_user_agent', 'hash_documento',
        ]));
    }
};
