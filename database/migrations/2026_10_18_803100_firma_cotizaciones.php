<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('cotizaciones', 'firma_cliente')) {
            Schema::table('cotizaciones', fn (Blueprint $t) => $t->text('firma_cliente')->nullable());
        }
    }

    public function down(): void
    {
        Schema::table('cotizaciones', fn (Blueprint $t) => $t->dropColumn('firma_cliente'));
    }
};
