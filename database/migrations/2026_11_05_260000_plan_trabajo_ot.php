<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orden_trabajo_equipo', function (Blueprint $t) {
            $t->text('trabajo_previsto')->nullable();
            $t->unsignedInteger('minutos_estimados')->nullable();
            $t->foreignId('tecnico_id')->nullable()->constrained('tecnicos');
        });

        Schema::table('ordenes_trabajo', function (Blueprint $t) {
            $t->text('alcance')->nullable();
            $t->json('condiciones')->nullable();
            $t->date('plazo_cliente')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orden_trabajo_equipo', function (Blueprint $t) {
            $t->dropConstrainedForeignId('tecnico_id');
            $t->dropColumn(['trabajo_previsto', 'minutos_estimados']);
        });
        Schema::table('ordenes_trabajo', function (Blueprint $t) {
            $t->dropColumn(['alcance', 'condiciones', 'plazo_cliente']);
        });
    }
};
