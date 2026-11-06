<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('plantilla_ajustes_cliente', function (Blueprint $t) {
            $t->string('ambito', 20)->default('ficha')->after('cliente_id');
            $t->foreignId('tipo_equipo_id')->nullable()->after('plantilla_id')
                ->constrained('tipos_equipo')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('plantilla_ajustes_cliente', function (Blueprint $t) {
            $t->dropConstrainedForeignId('tipo_equipo_id');
            $t->dropColumn('ambito');
        });
    }
};
