<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('informes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('orden_trabajo_id')->unique()->constrained('ordenes_trabajo')->cascadeOnDelete();
            $t->string('estado')->default('borrador');
            $t->string('ruta_pdf')->nullable();
            $t->string('token_verificacion', 64)->unique();
            $t->timestamp('generado_en')->nullable();
            $t->timestamp('publicado_en')->nullable();
            $t->foreignId('publicado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
        });

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
ALTER TABLE informes ADD CONSTRAINT informes_estado_check CHECK (estado IN ('borrador','publicado'));
ALTER TABLE informes ENABLE ROW LEVEL SECURITY;

CREATE POLICY interno_total ON informes USING (app_es_interno()) WITH CHECK (app_es_interno());
CREATE POLICY cliente_lectura ON informes FOR SELECT USING (
    estado = 'publicado'
    AND orden_trabajo_id IN (SELECT id FROM ordenes_trabajo WHERE cliente_id = app_cliente_id())
);
CREATE POLICY tecnico_lectura ON informes FOR SELECT USING (
    orden_trabajo_id IN (SELECT id FROM ordenes_trabajo WHERE tecnico_id = app_tecnico_id())
);

-- Verificación pública (sin sesión): solo devuelve datos mínimos de informes publicados.
CREATE OR REPLACE FUNCTION app_verificar_informe(p_token text)
RETURNS TABLE(numero text, publicado_en timestamp, cliente text)
LANGUAGE sql STABLE SECURITY DEFINER SET search_path = public AS $$
    SELECT o.numero::text, i.publicado_en::timestamp, c.razon_social::text
    FROM informes i
    JOIN ordenes_trabajo o ON o.id = i.orden_trabajo_id
    JOIN clientes c ON c.id = o.cliente_id
    WHERE i.token_verificacion = p_token AND i.estado = 'publicado' AND o.deleted_at IS NULL
$$;
REVOKE ALL ON FUNCTION app_verificar_informe(text) FROM PUBLIC;

DO $$ BEGIN
    IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'hvac_app') THEN
        GRANT SELECT, INSERT, UPDATE, DELETE ON informes TO hvac_app;
        GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO hvac_app;
        GRANT EXECUTE ON FUNCTION app_verificar_informe(text) TO hvac_app;
    END IF;
END $$;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('informes');

        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared('DROP FUNCTION IF EXISTS app_verificar_informe(text);');
        }
    }
};
