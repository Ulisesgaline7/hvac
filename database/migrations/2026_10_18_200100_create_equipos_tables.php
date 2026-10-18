<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('codigo_secuencias', function (Blueprint $t) {
            $t->string('prefijo', 10)->primary();
            $t->unsignedBigInteger('ultimo')->default(0);
        });

        Schema::create('equipos', function (Blueprint $t) {
            $t->id();
            $t->string('codigo')->unique();
            $t->string('token_qr', 64)->unique();
            $t->foreignId('area_id')->constrained('areas');
            $t->foreignId('tipo_equipo_id')->constrained('tipos_equipo');
            $t->foreignId('marca_id')->nullable()->constrained('marcas')->nullOnDelete();
            $t->string('modelo')->nullable();
            $t->string('serie')->nullable();
            $t->decimal('capacidad_valor', 10, 2)->nullable();
            $t->string('capacidad_unidad', 10)->nullable();
            $t->foreignId('refrigerante_id')->nullable()->constrained('refrigerantes')->nullOnDelete();
            $t->string('voltaje')->nullable();
            $t->string('corriente')->nullable();
            $t->jsonb('datos_placa')->nullable();
            $t->date('fecha_instalacion')->nullable();
            $t->date('garantia_hasta')->nullable();
            $t->string('estado')->default('operativo');
            $t->text('notas')->nullable();
            $t->timestamps();
            $t->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->softDeletes();
            $t->index('area_id');
            $t->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipos');
        Schema::dropIfExists('codigo_secuencias');
    }
};
