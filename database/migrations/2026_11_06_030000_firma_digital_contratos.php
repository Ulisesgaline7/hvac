<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('contratos', function (Blueprint $t) {
            $t->string('token_firma', 64)->nullable()->unique();
            $t->timestamp('firma_enviada_at')->nullable();
            $t->timestamp('firmado_at')->nullable();
            $t->string('firmado_por', 150)->nullable();
            $t->string('firmado_cargo', 150)->nullable();
            $t->string('firmado_ip', 45)->nullable();
            $t->string('firmado_user_agent', 500)->nullable();
            $t->string('hash_documento', 64)->nullable();
            $t->string('ruta_pdf')->nullable();
            $t->string('ruta_firma')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('contratos', function (Blueprint $t) {
            $t->dropColumn(['token_firma', 'firma_enviada_at', 'firmado_at', 'firmado_por', 'firmado_cargo',
                'firmado_ip', 'firmado_user_agent', 'hash_documento', 'ruta_pdf', 'ruta_firma']);
        });
    }
};
