<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('equipos', fn (Blueprint $t) => $t->string('foto_ruta')->nullable());
    }

    public function down(): void
    {
        Schema::table('equipos', fn (Blueprint $t) => $t->dropColumn('foto_ruta'));
    }
};
