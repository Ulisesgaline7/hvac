<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function comunes(Blueprint $t): void
    {
        $t->timestamps();
        $t->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
        $t->softDeletes();
    }

    public function up(): void
    {
        Schema::create('marcas', function (Blueprint $t) {
            $t->id();
            $t->string('nombre')->unique();
            $this->comunes($t);
        });

        Schema::create('refrigerantes', function (Blueprint $t) {
            $t->id();
            $t->string('nombre')->unique();
            $this->comunes($t);
        });

        Schema::create('tipos_equipo', function (Blueprint $t) {
            $t->id();
            $t->string('nombre')->unique();
            $t->string('familia');
            $t->string('prefijo_codigo', 10);
            $t->unsignedBigInteger('plantilla_id')->nullable();
            $this->comunes($t);
        });

        Schema::create('clientes', function (Blueprint $t) {
            $t->id();
            $t->string('razon_social');
            $t->string('identificacion_fiscal')->nullable()->unique();
            $t->string('estado')->default('activo');
            $t->jsonb('contactos')->nullable();
            $t->text('notas')->nullable();
            $this->comunes($t);
        });

        Schema::create('sucursales', function (Blueprint $t) {
            $t->id();
            $t->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $t->string('nombre');
            $t->string('direccion')->nullable();
            $t->decimal('latitud', 10, 7)->nullable();
            $t->decimal('longitud', 10, 7)->nullable();
            $t->text('horarios_acceso')->nullable();
            $t->jsonb('contactos')->nullable();
            $this->comunes($t);
            $t->index('cliente_id');
        });

        Schema::create('areas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('sucursal_id')->constrained('sucursales')->cascadeOnDelete();
            $t->string('nombre');
            $this->comunes($t);
            $t->index('sucursal_id');
        });

        Schema::table('users', function (Blueprint $t) {
            $t->foreignId('cliente_id')->nullable()->after('id')->constrained('clientes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropConstrainedForeignId('cliente_id'));
        Schema::dropIfExists('areas');
        Schema::dropIfExists('sucursales');
        Schema::dropIfExists('clientes');
        Schema::dropIfExists('tipos_equipo');
        Schema::dropIfExists('refrigerantes');
        Schema::dropIfExists('marcas');
    }
};
