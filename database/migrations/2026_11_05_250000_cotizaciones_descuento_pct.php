<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cotizaciones', function (Blueprint $t) {
            $t->decimal('descuento_contrato_pct', 5, 4)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $t) {
            $t->dropColumn('descuento_contrato_pct');
        });
    }
};
