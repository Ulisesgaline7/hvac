<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tecnicos', function (Blueprint $t) {
            $t->string('nivel', 20)->nullable();
            $t->string('modalidad', 20)->default('individual');
            $t->decimal('retencion_pct', 5, 2)->nullable();
        });

        Schema::create('tarifas_tecnico', function (Blueprint $t) {
            $t->id();
            $t->string('codigo', 20)->unique();
            $t->decimal('monto', 10, 2);
            $t->string('nota')->nullable();
            $t->timestamps();
        });

        Schema::create('liquidaciones_tecnico', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tecnico_id')->constrained('tecnicos');
            $t->date('semana_inicio');
            $t->date('semana_fin');
            $t->decimal('bruto', 12, 2)->default(0);
            $t->decimal('retencion_pct', 5, 2)->nullable();
            $t->decimal('retencion', 12, 2)->default(0);
            $t->decimal('neto', 12, 2)->default(0);
            $t->string('estado', 20)->default('borrador');
            $t->timestamp('pagada_en')->nullable();
            $t->string('referencia_pago')->nullable();
            $t->text('notas')->nullable();
            $t->timestamps();
            $t->unique(['tecnico_id', 'semana_inicio']);
        });

        Schema::create('pagos_tecnico', function (Blueprint $t) {
            $t->id();
            $t->foreignId('orden_trabajo_id')->constrained('ordenes_trabajo');
            $t->foreignId('orden_trabajo_equipo_id')->nullable()->constrained('orden_trabajo_equipo');
            $t->foreignId('tecnico_id')->constrained('tecnicos');
            $t->foreignId('liquidacion_id')->nullable()->constrained('liquidaciones_tecnico');
            $t->string('rol', 20);
            $t->string('codigo_servicio', 20)->nullable();
            $t->string('concepto');
            $t->decimal('monto_base', 10, 2)->default(0);
            $t->decimal('porcentaje', 5, 2)->default(100);
            $t->decimal('monto', 10, 2)->default(0);
            $t->string('estado', 20)->default('pendiente');
            $t->text('notas')->nullable();
            $t->timestamps();
            $t->index(['tecnico_id', 'estado']);
            $t->index('orden_trabajo_id');
        });

        $ahora = now();
        $tarifas = [
            ['T-01', 300, 'Preventivo'], ['T-02', 300, 'Preventivo'], ['T-03', 300, 'Preventivo'],
            ['T-22', 200, 'Diagnóstico'],
            ['T-10', 850, 'Instalación 12,000 BTU'],
            ['T-11', 1000, 'Instalación 18,000 a 24,000 BTU: el documento da 1,000 y 1,250; validar'],
            ['T-16', 400, 'Desinstalación'],
            ['T-25', 300, 'Reparación eléctrica sencilla'],
            ['T-23', 550, 'Fuga y vacío'],
        ];
        foreach ($tarifas as [$codigo, $monto, $nota]) {
            DB::table('tarifas_tecnico')->insert([
                'codigo' => $codigo, 'monto' => $monto, 'nota' => $nota,
                'created_at' => $ahora, 'updated_at' => $ahora,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_tecnico');
        Schema::dropIfExists('liquidaciones_tecnico');
        Schema::dropIfExists('tarifas_tecnico');
        Schema::table('tecnicos', function (Blueprint $t) {
            $t->dropColumn(['nivel', 'modalidad', 'retencion_pct']);
        });
    }
};
