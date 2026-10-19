<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facturas', function (Blueprint $t) {
            $t->id();
            $t->string('numero')->unique();
            $t->string('estado')->default('emitida'); // emitida | parcial | pagada | anulada
            $t->foreignId('cliente_id')->constrained('clientes');
            $t->foreignId('sucursal_id')->nullable()->constrained('sucursales')->nullOnDelete();
            $t->foreignId('orden_trabajo_id')->nullable()->constrained('ordenes_trabajo')->nullOnDelete();
            $t->foreignId('cotizacion_id')->nullable()->constrained('cotizaciones')->nullOnDelete();
            $t->date('fecha');
            $t->date('vence');
            $t->decimal('subtotal', 12, 2)->default(0);
            $t->decimal('isv', 12, 2)->default(0);
            $t->decimal('total', 12, 2)->default(0);
            $t->decimal('pagado', 12, 2)->default(0);
            $t->text('notas')->nullable();
            $t->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();

            $t->unique('orden_trabajo_id'); // una factura por OT
            $t->index(['cliente_id', 'estado']);
        });

        Schema::create('pagos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('factura_id')->nullable()->constrained('facturas')->cascadeOnDelete();
            $t->foreignId('orden_trabajo_id')->nullable()->constrained('ordenes_trabajo')->nullOnDelete();
            $t->foreignId('cliente_id')->constrained('clientes');
            $t->string('tipo')->default('pago'); // anticipo | pago
            $t->decimal('monto', 12, 2);
            $t->date('fecha');
            $t->string('metodo')->nullable();     // efectivo, transferencia, cheque...
            $t->string('referencia')->nullable();
            $t->text('notas')->nullable();
            $t->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();

            $t->index(['orden_trabajo_id', 'tipo']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE facturas ADD CONSTRAINT factura_estado_check CHECK (estado IN ('emitida','parcial','pagada','anulada'))");
            DB::statement("ALTER TABLE pagos ADD CONSTRAINT pago_tipo_check CHECK (tipo IN ('anticipo','pago'))");
            DB::statement('ALTER TABLE pagos ADD CONSTRAINT pago_monto_check CHECK (monto > 0)');

            foreach (['facturas', 'pagos'] as $tabla) {
                DB::statement("ALTER TABLE {$tabla} ENABLE ROW LEVEL SECURITY");
                DB::statement("CREATE POLICY interno_total ON {$tabla} USING (app_es_interno()) WITH CHECK (app_es_interno())");
                DB::statement("CREATE POLICY cliente_lectura ON {$tabla} FOR SELECT USING (cliente_id = app_cliente_id())");
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
        Schema::dropIfExists('facturas');
    }
};
