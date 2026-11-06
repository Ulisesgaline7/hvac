<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('equipos', function (Blueprint $t) {
            // 'existente' = ya estaba instalado; 'propia' = lo instalamos nosotros
            $t->string('origen', 20)->default('existente')->after('estado');
            $t->foreignId('orden_instalacion_id')->nullable()
                ->constrained('ordenes_trabajo')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('equipos', function (Blueprint $t) {
            $t->dropConstrainedForeignId('orden_instalacion_id');
            $t->dropColumn('origen');
        });
    }
};
