<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ordenes_trabajo', 'diagnostico')) {
            Schema::table('ordenes_trabajo', function (Blueprint $t) {
                $t->text('diagnostico')->nullable();
                $t->text('recomendaciones')->nullable();
            });
        }

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE ordenes_trabajo DROP CONSTRAINT IF EXISTS ot_tipo_check');
            DB::statement("ALTER TABLE ordenes_trabajo ADD CONSTRAINT ot_tipo_check CHECK (tipo::text IN ('preventivo','correctivo','instalacion','emergencia','visita_tecnica'))");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE ordenes_trabajo DROP CONSTRAINT IF EXISTS ot_tipo_check');
            DB::statement("ALTER TABLE ordenes_trabajo ADD CONSTRAINT ot_tipo_check CHECK (tipo::text IN ('preventivo','correctivo','instalacion','emergencia'))");
        }
        Schema::table('ordenes_trabajo', fn (Blueprint $t) => $t->dropColumn(['diagnostico', 'recomendaciones']));
    }
};
