<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plantilla_ajustes_cliente', function (Blueprint $t) {
            $t->id();
            $t->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $t->foreignId('plantilla_id')->nullable()->constrained('plantillas_formulario')->cascadeOnDelete();
            $t->jsonb('ajustes');
            $t->boolean('activo')->default(true);
            $t->text('notas')->nullable();
            $t->timestamps();
            $t->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->index(['cliente_id', 'activo']);
        });

        Schema::table('fichas_tecnicas', function (Blueprint $t) {
            $t->jsonb('definicion')->nullable()->after('plantilla_version_id');
        });
    }

    public function down(): void
    {
        Schema::table('fichas_tecnicas', fn (Blueprint $t) => $t->dropColumn('definicion'));
        Schema::dropIfExists('plantilla_ajustes_cliente');
    }
};
