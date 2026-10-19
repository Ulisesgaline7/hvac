<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['diagnostico', 'recomendaciones'] as $col) {
            if (! Schema::hasColumn('ordenes_trabajo', $col)) {
                Schema::table('ordenes_trabajo', fn (Blueprint $t) => $t->text($col)->nullable());
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ordenes_trabajo', 'recomendaciones')) {
            Schema::table('ordenes_trabajo', fn (Blueprint $t) => $t->dropColumn('recomendaciones'));
        }
    }
};
