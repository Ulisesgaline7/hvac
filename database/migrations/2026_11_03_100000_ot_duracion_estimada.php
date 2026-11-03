<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordenes_trabajo', function (Blueprint $t) {
            if (! Schema::hasColumn('ordenes_trabajo', 'hora_programada')) {
                $t->time('hora_programada')->nullable();
            }

            $t->unsignedSmallInteger('duracion_estimada_min')->nullable();
            $t->index(['tecnico_id', 'fecha_programada'], 'ot_agenda_tecnico_idx');
        });
    }

    public function down(): void
    {
        Schema::table('ordenes_trabajo', function (Blueprint $t) {
            $t->dropIndex('ot_agenda_tecnico_idx');
            $t->dropColumn('duracion_estimada_min');
        });
    }
};
