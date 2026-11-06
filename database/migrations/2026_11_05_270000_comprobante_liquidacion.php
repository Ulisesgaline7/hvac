<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('liquidaciones_tecnico', function (Blueprint $t) {
            $t->string('numero', 30)->nullable()->unique();
            $t->string('metodo_pago', 20)->nullable();
            $t->string('recibe_nombre')->nullable();
            $t->foreignId('pagada_por')->nullable()->constrained('users');
            $t->timestamp('anulada_en')->nullable();
            $t->text('motivo_anulacion')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('liquidaciones_tecnico', function (Blueprint $t) {
            $t->dropConstrainedForeignId('pagada_por');
            $t->dropColumn(['numero', 'metodo_pago', 'recibe_nombre', 'anulada_en', 'motivo_anulacion']);
        });
    }
};
