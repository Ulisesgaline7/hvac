<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    private array $tablas = ['garantias', 'pagos', 'facturas'];

    public function up(): void
    {
        foreach ($this->tablas as $tabla) {
            if (! Schema::hasColumn($tabla, 'token_verificacion')) {
                Schema::table($tabla, fn (Blueprint $t) => $t->string('token_verificacion', 48)->nullable()->unique());
            }
            foreach (DB::table($tabla)->whereNull('token_verificacion')->pluck('id') as $id) {
                DB::table($tabla)->where('id', $id)->update(['token_verificacion' => Str::random(48)]);
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tablas as $tabla) {
            Schema::table($tabla, fn (Blueprint $t) => $t->dropColumn('token_verificacion'));
        }
    }
};
