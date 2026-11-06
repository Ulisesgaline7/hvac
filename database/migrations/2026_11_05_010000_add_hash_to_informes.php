<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('informes', function (Blueprint $t) {
            $t->string('hash_pdf', 64)->nullable();
            $t->timestamp('firmado_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('informes', function (Blueprint $t) {
            $t->dropColumn(['hash_pdf', 'firmado_en']);
        });
    }
};
