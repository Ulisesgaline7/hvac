<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tablas = ['ordenes_trabajo', 'cotizaciones', 'contratos', 'servicios_catalogo', 'tipos_equipo'];

    public function up(): void
    {
        Schema::create('divisiones', function (Blueprint $t) {
            $t->id();
            $t->string('codigo', 20)->unique();
            $t->string('nombre');
            $t->text('descripcion')->nullable();
            $t->unsignedTinyInteger('etapa')->default(1);
            $t->boolean('activa')->default(false);
            $t->unsignedSmallInteger('orden')->default(0);
            $t->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        $divisiones = [
            ['CLIMA', 'CT CLIMA', 'Refrigeración y aire acondicionado', 1, true],
            ['SECURITY', 'CT SECURITY', 'Cámaras, alarmas y GPS', 1, false],
            ['ELECTRIC', 'CT ELECTRIC', 'Electricidad', 1, false],
            ['POWER', 'CT POWER', 'Generadores eléctricos', 1, false],
            ['SOLAR', 'CT SOLAR', 'Paneles solares', 1, false],
            ['AQUA', 'CT AQUA', 'Tratamiento de agua', 2, false],
            ['HYDRO', 'CT HYDRO', 'Sistemas hidráulicos', 2, false],
            ['AUTOMATION', 'CT AUTOMATION', 'Automatización industrial', 2, false],
            ['NETWORK', 'CT NETWORK', 'Redes y cableado', 2, false],
            ['FIRE', 'CT FIRE', 'Sistemas contra incendios', 3, false],
            ['ACCESS', 'CT ACCESS', 'Control de acceso', 3, false],
            ['SMART', 'CT SMART', 'Domótica', 3, false],
            ['LIGHTING', 'CT LIGHTING', 'Iluminación', 3, false],
            ['FACILITY', 'CT FACILITY', 'Mantenimiento de edificios', 4, false],
            ['BUILDING', 'CT BUILDING', 'Proyectos y adecuaciones', 4, false],
            ['ENVIRONMENT', 'CT ENVIRONMENT', 'Soluciones ambientales', 4, false],
        ];

        foreach ($divisiones as $i => [$codigo, $nombre, $descripcion, $etapa, $activa]) {
            DB::table('divisiones')->insert([
                'codigo' => $codigo, 'nombre' => $nombre, 'descripcion' => $descripcion,
                'etapa' => $etapa, 'activa' => $activa, 'orden' => $i + 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $clima = (int) DB::table('divisiones')->where('codigo', 'CLIMA')->value('id');

        foreach ($this->tablas as $tabla) {
            Schema::table($tabla, function (Blueprint $t) {
                $t->foreignId('division_id')->nullable()->constrained('divisiones')->nullOnDelete();
                $t->index('division_id');
            });
            DB::table($tabla)->update(['division_id' => $clima]);
            DB::statement("alter table {$tabla} alter column division_id set default {$clima}");
        }

        Schema::create('tecnico_division', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tecnico_id')->constrained('tecnicos')->cascadeOnDelete();
            $t->foreignId('division_id')->constrained('divisiones')->cascadeOnDelete();
            $t->boolean('habilitado')->default(true);
            $t->date('vencimiento')->nullable();
            $t->text('notas')->nullable();
            $t->timestamps();
            $t->unique(['tecnico_id', 'division_id']);
        });

        DB::statement("
            insert into tecnico_division (tecnico_id, division_id, habilitado, created_at, updated_at)
            select id, {$clima}, estado_homologacion = 'aprobado', now(), now() from tecnicos
        ");

        DB::statement('alter table divisiones enable row level security');
        DB::statement('create policy interno_total on divisiones using (app_es_interno()) with check (app_es_interno())');
        DB::statement('create policy lectura_general on divisiones for select using (true)');

        DB::statement('alter table tecnico_division enable row level security');
        DB::statement('create policy interno_total on tecnico_division using (app_es_interno()) with check (app_es_interno())');
        DB::statement('create policy propio_lectura on tecnico_division for select using (tecnico_id in (select id from tecnicos where user_id = app_user_id()))');
    }

    public function down(): void
    {
        foreach ($this->tablas as $tabla) {
            Schema::table($tabla, function (Blueprint $t) {
                $t->dropConstrainedForeignId('division_id');
            });
        }
        Schema::dropIfExists('tecnico_division');
        Schema::dropIfExists('divisiones');
    }
};
