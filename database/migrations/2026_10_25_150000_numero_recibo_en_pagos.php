<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('pagos', 'numero')) {
            Schema::table('pagos', function (Blueprint $t) {
                $t->string('numero')->nullable()->unique(); // REC-aaaa-000001
            });
        }

        // Pagos ya existentes: se numeran con su año y su id.
        DB::table('pagos')->whereNull('numero')->orderBy('id')->each(function ($p) {
            DB::table('pagos')->where('id', $p->id)->update([
                'numero' => sprintf('REC-%s-%06d', substr((string) $p->created_at, 0, 4), $p->id),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $t) {
            $t->dropUnique(['numero']);
            $t->dropColumn('numero');
        });
    }
};
