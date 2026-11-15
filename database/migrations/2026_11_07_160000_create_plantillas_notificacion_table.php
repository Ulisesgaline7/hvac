<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plantillas_notificacion', function (Blueprint $t) {
            $t->id();
            $t->string('clave')->unique();
            $t->string('nombre');
            $t->text('descripcion')->nullable();
            $t->string('titulo');
            $t->text('cuerpo');
            $t->string('push_titulo')->default('Control Total');
            $t->string('push_cuerpo')->default('Tienes un aviso nuevo');
            $t->boolean('correo_activo')->default(true);
            $t->boolean('push_activo')->default(true);
            $t->boolean('avisar_tecnico')->default(true);
            $t->jsonb('roles_extra')->nullable();
            $t->boolean('activa')->default(true);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plantillas_notificacion');
    }
};
