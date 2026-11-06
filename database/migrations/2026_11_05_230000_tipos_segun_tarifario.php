<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    private function comunes(): array
    {
        return [
            ['tipo' => 'numero', 'clave' => 'altura_instalacion', 'etiqueta' => 'Altura de instalación', 'unidad' => 'm', 'obligatorio' => false, 'min' => 0, 'max' => 30],
            ['tipo' => 'seleccion', 'clave' => 'tipo_acceso', 'etiqueta' => 'Tipo de acceso', 'obligatorio' => false, 'opciones' => ['Normal', 'Azotea', 'Cielo falso cerrado', 'Espacio confinado']],
            ['tipo' => 'seleccion', 'clave' => 'fases', 'etiqueta' => 'Fases', 'obligatorio' => false, 'opciones' => ['Monofásico', 'Trifásico']],
        ];
    }

    private function aire(array $unidades, array $tiposUnidad): array
    {
        return array_merge([
            ['tipo' => 'numero', 'clave' => 'capacidad', 'etiqueta' => 'Capacidad', 'obligatorio' => false],
            ['tipo' => 'seleccion', 'clave' => 'capacidad_unidad', 'etiqueta' => 'Unidad de capacidad', 'obligatorio' => false, 'opciones' => $unidades],
            ['tipo' => 'seleccion', 'clave' => 'tipo_unidad', 'etiqueta' => 'Tipo de unidad', 'obligatorio' => false, 'opciones' => $tiposUnidad],
            ['tipo' => 'seleccion', 'clave' => 'refrigerante_tipo', 'etiqueta' => 'Tipo de refrigerante', 'obligatorio' => false, 'opciones' => ['R410A', 'R22', 'R134a', 'Otro']],
            ['tipo' => 'numero', 'clave' => 'seer', 'etiqueta' => 'SEER', 'obligatorio' => false, 'min' => 8, 'max' => 40],
            ['tipo' => 'seleccion', 'clave' => 'tipo_compresor', 'etiqueta' => 'Tipo de compresor', 'obligatorio' => false, 'opciones' => ['Scroll', 'Rotativo', 'Reciprocante', 'Inverter']],
            ['tipo' => 'numero', 'clave' => 'metros_tuberia', 'etiqueta' => 'Metros de tubería instalada', 'unidad' => 'm', 'obligatorio' => false, 'min' => 0],
        ], $this->comunes());
    }

    private function comercial(array $unidades, bool $deshielo = true): array
    {
        $c = [
            ['tipo' => 'numero', 'clave' => 'capacidad', 'etiqueta' => 'Capacidad', 'obligatorio' => false],
            ['tipo' => 'seleccion', 'clave' => 'capacidad_unidad', 'etiqueta' => 'Unidad de capacidad', 'obligatorio' => false, 'opciones' => $unidades],
            ['tipo' => 'seleccion', 'clave' => 'refrigerante_tipo', 'etiqueta' => 'Tipo de refrigerante', 'obligatorio' => false, 'opciones' => ['R410A', 'R22', 'R134a', 'R404A', 'Otro']],
            ['tipo' => 'numero', 'clave' => 'temperatura_operacion', 'etiqueta' => 'Temperatura de operación', 'unidad' => '°C', 'obligatorio' => false, 'min' => -40, 'max' => 15],
            ['tipo' => 'seleccion', 'clave' => 'tipo_compresor', 'etiqueta' => 'Tipo de compresor', 'obligatorio' => false, 'opciones' => ['Hermético', 'Semihermético', 'Scroll']],
        ];
        if ($deshielo) {
            $c[] = ['tipo' => 'seleccion', 'clave' => 'tipo_deshielo', 'etiqueta' => 'Tipo de deshielo', 'obligatorio' => false, 'opciones' => ['Eléctrico', 'Gas caliente', 'Por aire']];
        }

        return array_merge($c, $this->comunes());
    }

    private function cocina(): array
    {
        return [
            ['tipo' => 'seleccion', 'clave' => 'energia', 'etiqueta' => 'Tipo de energía', 'obligatorio' => false, 'opciones' => ['Eléctrico', 'Gas LP', 'Gas natural']],
            ['tipo' => 'numero', 'clave' => 'quemadores', 'etiqueta' => 'Quemadores / secciones', 'obligatorio' => false, 'min' => 0],
            ['tipo' => 'seleccion', 'clave' => 'tipo_acceso', 'etiqueta' => 'Tipo de acceso', 'obligatorio' => false, 'opciones' => ['Normal', 'Azotea', 'Cielo falso cerrado', 'Espacio confinado']],
        ];
    }

    public function up(): void
    {
        $ahora = now();

        // 1) Renombrar existentes al lenguaje del tarifario
        $renombres = [
            'Split' => 'Mini split / pared',
            'Cassette' => 'Piso-techo / cassette / ducto',
            'Paquete' => 'Paquete / rooftop',
        ];
        foreach ($renombres as $viejo => $nuevo) {
            DB::table('tipos_equipo')->whereNull('deleted_at')->where('nombre', $viejo)
                ->update(['nombre' => $nuevo, 'updated_at' => $ahora]);
        }

        // 2) Crear tipos faltantes (heredan plantilla_id de su familia)
        $plantillaDe = fn (string $f) => DB::table('tipos_equipo')->whereNull('deleted_at')
            ->where('familia', $f)->whereNotNull('plantilla_id')->value('plantilla_id');

        $nuevos = [
            ['Manejadora (UMA)', 'aire_acondicionado'],
            ['Refrigerador', 'refrigeracion_comercial'],
            ['Cámara de congelación', 'cuartos_frios'],
            ['Estufa / cocina industrial', 'cocina_industrial'],
            ['Plancha industrial', 'cocina_industrial'],
            ['Horno eléctrico / a gas', 'cocina_industrial'],
            ['Freidora', 'cocina_industrial'],
            ['Campana de extracción', 'cocina_industrial'],
        ];
        foreach ($nuevos as [$nombre, $familia]) {
            $existe = DB::table('tipos_equipo')->whereNull('deleted_at')->where('nombre', $nombre)->exists();
            if ($existe) {
                continue;
            }
            DB::table('tipos_equipo')->insert([
                'nombre' => $nombre,
                'familia' => $familia,
                'prefijo_codigo' => 'EQ',
                'plantilla_id' => $plantillaDe($familia),
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);
        }

        // 3) Reescribir campos_registro según tarifario
        $porNombre = [
            'Mini split / pared' => $this->aire(['BTU/h', 'TR'], ['Mini split', 'Pared']),
            'Piso-techo / cassette / ducto' => $this->aire(['BTU/h', 'TR'], ['Piso-techo', 'Cassette', 'Ducto']),
            'Paquete / rooftop' => $this->aire(['TR', 'BTU/h'], ['Paquete', 'Rooftop']),
            'Manejadora (UMA)' => $this->aire(['TR', 'BTU/h'], ['Manejadora']),
            'Chiller' => $this->aire(['TR', 'kW'], ['Chiller']),
            'Cuarto frío' => $this->comercial(['HP', 'BTU/h', 'kW']),
            'Cámara de congelación' => $this->comercial(['HP', 'BTU/h', 'kW']),
            'Congelador' => $this->comercial(['HP', 'BTU/h', 'kW']),
            'Refrigerador' => $this->comercial(['HP', 'BTU/h', 'kW']),
            'Mesa fría' => $this->comercial(['HP', 'BTU/h', 'kW']),
            'Vitrina' => $this->comercial(['HP', 'BTU/h', 'kW']),
            'Máquina de hielo' => $this->comercial(['HP', 'kW', 'BTU/h'], false),
        ];
        foreach (['Estufa / cocina industrial', 'Plancha industrial', 'Horno eléctrico / a gas', 'Freidora', 'Campana de extracción'] as $n) {
            $porNombre[$n] = $this->cocina();
        }

        foreach ($porNombre as $nombre => $campos) {
            DB::table('tipos_equipo')->whereNull('deleted_at')->where('nombre', $nombre)
                ->update([
                    'campos_registro' => json_encode($campos, JSON_UNESCAPED_UNICODE),
                    'updated_at' => $ahora,
                ]);
        }
    }

    public function down(): void
    {
        $ahora = now();
        foreach (['Mini split / pared' => 'Split', 'Piso-techo / cassette / ducto' => 'Cassette', 'Paquete / rooftop' => 'Paquete'] as $nuevo => $viejo) {
            DB::table('tipos_equipo')->where('nombre', $nuevo)->update(['nombre' => $viejo, 'updated_at' => $ahora]);
        }
    }
};
