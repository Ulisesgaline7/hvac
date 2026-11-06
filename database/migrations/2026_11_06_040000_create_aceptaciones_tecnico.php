<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aceptaciones_tecnico', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tecnico_id')->constrained('tecnicos');
            $t->foreignId('documento_id')->constrained('documentos_catalogo');
            $t->foreignId('orden_trabajo_id')->nullable()->constrained('ordenes_trabajo')->nullOnDelete();
            $t->string('version', 50)->nullable();
            $t->string('hash_texto', 64);
            $t->string('firmado_nombre', 150);
            $t->timestamp('aceptado_at');
            $t->string('ip', 45)->nullable();
            $t->string('user_agent', 500)->nullable();
            $t->string('ruta_firma');
            $t->timestamps();
            $t->unique(['tecnico_id', 'documento_id', 'hash_texto']);
        });

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
ALTER TABLE aceptaciones_tecnico ENABLE ROW LEVEL SECURITY;

CREATE POLICY interno_total ON aceptaciones_tecnico USING (app_es_interno()) WITH CHECK (app_es_interno());
CREATE POLICY tecnico_lectura ON aceptaciones_tecnico FOR SELECT USING (tecnico_id = app_tecnico_id());
CREATE POLICY tecnico_inserta ON aceptaciones_tecnico FOR INSERT WITH CHECK (tecnico_id = app_tecnico_id());

DO $$ BEGIN
    IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'hvac_app') THEN
        -- Solo lectura e inserción: una aceptación firmada no se modifica ni se borra desde la app.
        GRANT SELECT, INSERT ON aceptaciones_tecnico TO hvac_app;
        GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO hvac_app;
    END IF;
END $$;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('aceptaciones_tecnico');
    }
};
