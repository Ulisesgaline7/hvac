<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('servicios_web', function (Blueprint $t) {
            $t->id();
            $t->string('nombre', 120);
            $t->text('descripcion');
            $t->decimal('precio_desde', 12, 2)->nullable();
            $t->string('moneda', 4)->default('L');
            $t->string('unidad', 60)->nullable();
            $t->string('imagen')->nullable();
            $t->unsignedInteger('orden')->default(0);
            $t->boolean('visible')->default(true);
            $t->timestamps();
        });

        Schema::create('ajustes_web', function (Blueprint $t) {
            $t->id();
            $t->string('clave', 60)->unique();
            $t->string('etiqueta');
            $t->string('grupo', 40)->default('General');
            $t->text('valor')->nullable();
            $t->string('ayuda')->nullable();
            $t->unsignedInteger('orden')->default(0);
            $t->timestamps();
        });

        $now = now();

        $servicios = [
            ['Aire acondicionado', 'Instalación, mantenimiento y reparación de equipos para casas, oficinas, clínicas y locales.'],
            ['Refrigeración comercial e industrial', 'Cámaras frigoríficas, neveras, vitrinas y equipos para restaurantes, supermercados e industria.'],
            ['Ventilación', 'Ventilación, extracción y tratamiento del aire, con asesoría para instalaciones pequeñas y grandes.'],
            ['Mantenimiento y contratos', 'Visitas programadas con cuota mensual para que tus equipos no fallen y consuman menos energía.'],
            ['Reparaciones y emergencias', 'Diagnóstico y reparación de fallas. Atendemos emergencias para que no pierdas producto ni comodidad.'],
            ['Instalación de equipos nuevos', 'Te asesoramos sobre la capacidad que necesitas, instalamos y dejamos el equipo identificado y con garantía.'],
        ];
        foreach ($servicios as $i => [$nombre, $desc]) {
            DB::table('servicios_web')->insert([
                'nombre' => $nombre, 'descripcion' => $desc, 'orden' => $i + 1,
                'visible' => true, 'moneda' => 'L', 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        $ajustes = [
            ['hero_titulo', 'Título de la portada', 'Portada', 'Frío y clima bajo control total.', null],
            ['hero_texto', 'Texto de la portada', 'Portada', 'Instalamos, mantenemos y reparamos aire acondicionado, refrigeración y ventilación. Cada trabajo queda registrado y tú puedes darle seguimiento en línea.', null],
            ['telefono', 'Teléfono', 'Contacto', null, 'Ej. +504 0000-0000. Vacío = no se muestra.'],
            ['whatsapp', 'WhatsApp', 'Contacto', null, 'Solo números con código de país, ej. 50400000000.'],
            ['correo', 'Correo', 'Contacto', null, 'Vacío = no se muestra.'],
            ['horario', 'Horario', 'Contacto', null, 'Ej. Lunes a sábado, 7:00 a 18:00.'],
            ['direccion', 'Dirección', 'Contacto', null, null],
            ['ciudades', 'Ciudades donde atienden', 'Cobertura', null, 'Sepáralas con comas. Vacío = la sección no se muestra.'],
        ];
        foreach ($ajustes as $i => [$clave, $etiqueta, $grupo, $valor, $ayuda]) {
            DB::table('ajustes_web')->insert([
                'clave' => $clave, 'etiqueta' => $etiqueta, 'grupo' => $grupo, 'valor' => $valor,
                'ayuda' => $ayuda, 'orden' => $i + 1, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ajustes_web');
        Schema::dropIfExists('servicios_web');
    }
};
