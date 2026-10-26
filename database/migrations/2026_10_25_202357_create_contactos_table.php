<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contactos', function (Blueprint $t) {
            $t->id();
            $t->string('nombre');
            $t->string('telefono', 30);
            $t->string('email')->nullable();
            $t->string('ciudad')->nullable();
            $t->string('servicio')->nullable();
            $t->text('mensaje');
            $t->boolean('atendido')->default(false);
            $t->string('ip', 45)->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contactos');
    }
};
