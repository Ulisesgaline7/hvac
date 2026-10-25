<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizacion_equipo', function (Blueprint $t) {
            $t->id();
            $t->foreignId('cotizacion_id')->constrained('cotizaciones')->cascadeOnDelete();
            $t->foreignId('equipo_id')->constrained('equipos');
            $t->timestamps();
            $t->unique(['cotizacion_id', 'equipo_id']);
        });

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
ALTER TABLE cotizacion_equipo ENABLE ROW LEVEL SECURITY;
CREATE POLICY interno_total ON cotizacion_equipo USING (app_es_interno()) WITH CHECK (app_es_interno());
DO $$ BEGIN
    IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'hvac_app') THEN
        GRANT SELECT, INSERT, UPDATE, DELETE ON cotizacion_equipo TO hvac_app;
        GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO hvac_app;
    END IF;
END $$;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizacion_equipo');
    }
};
