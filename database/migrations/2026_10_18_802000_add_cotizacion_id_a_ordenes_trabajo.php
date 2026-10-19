<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('ordenes_trabajo', 'cotizacion_id')) {
            return;
        }

        Schema::table('ordenes_trabajo', function (Blueprint $t) {
            $t->foreignId('cotizacion_id')->nullable()->constrained('cotizaciones')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('ordenes_trabajo', 'cotizacion_id')) {
            Schema::table('ordenes_trabajo', fn (Blueprint $t) => $t->dropConstrainedForeignId('cotizacion_id'));
        }
    }
};
