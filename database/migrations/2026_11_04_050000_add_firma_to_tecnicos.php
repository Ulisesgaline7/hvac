<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tecnicos', function (Blueprint $t) {
            $t->text('firma_imagen')->nullable();
            $t->string('cargo')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tecnicos', function (Blueprint $t) {
            $t->dropColumn(['firma_imagen', 'cargo']);
        });
    }
};
