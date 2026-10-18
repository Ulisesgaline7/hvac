<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordenes_trabajo', function (Blueprint $t) {
            $t->time('hora_programada')->nullable()->after('fecha_programada');
            $t->text('materiales_autorizados')->nullable()->after('descripcion');
            $t->decimal('precio_estimado', 12, 2)->nullable()->after('materiales_autorizados');
            $t->decimal('anticipo', 12, 2)->nullable()->after('precio_estimado');
            // Seguridad antes de iniciar: epp_listo, energia_bloqueada, altura_evaluada
            $t->jsonb('seguridad_previa')->nullable()->after('anticipo');
        });
    }

    public function down(): void
    {
        Schema::table('ordenes_trabajo', function (Blueprint $t) {
            $t->dropColumn(['hora_programada', 'materiales_autorizados', 'precio_estimado', 'anticipo', 'seguridad_previa']);
        });
    }
};
