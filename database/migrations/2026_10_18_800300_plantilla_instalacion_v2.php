<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const ESTADO = ['OK', 'Con observación', 'N/A'];

    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::select("select set_config('app.es_interno','true',false)");
        }

        $plantilla = DB::table('plantillas_formulario')->where('familia', 'instalacion')->whereNull('deleted_at')->first();
        if (! $plantilla) {
            return;
        }

        $ultima = (int) DB::table('plantilla_versiones')->where('plantilla_id', $plantilla->id)->max('version');

        DB::table('plantilla_versiones')->insert([
            'plantilla_id' => $plantilla->id,
            'version' => $ultima + 1,
            'definicion' => json_encode($this->definicion(), JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::select("select set_config('app.es_interno','true',false)");
        }

        $p = DB::table('plantillas_formulario')->where('familia', 'instalacion')->first();
        $v = $p ? DB::table('plantilla_versiones')->where('plantilla_id', $p->id)->orderByDesc('version')->first() : null;
        if ($v && $v->version > 1 && ! DB::table('fichas_tecnicas')->where('plantilla_version_id', $v->id)->exists()) {
            DB::table('plantilla_versiones')->where('id', $v->id)->delete();
        }
    }

    private function rev(string $clave, string $etiqueta): array
    {
        return ['tipo' => 'seleccion', 'clave' => $clave, 'etiqueta' => $etiqueta, 'opciones' => self::ESTADO, 'obligatorio' => false];
    }

    private function num(string $clave, string $etiqueta, string $unidad): array
    {
        return ['tipo' => 'numero', 'clave' => $clave, 'unidad' => $unidad, 'etiqueta' => $etiqueta, 'obligatorio' => false];
    }

    private function definicion(): array
    {
        return [
            ['tipo' => 'texto', 'clave' => 'marca', 'etiqueta' => 'Marca', 'obligatorio' => true],
            ['tipo' => 'texto', 'clave' => 'modelo', 'etiqueta' => 'Modelo', 'obligatorio' => true],
            ['tipo' => 'texto', 'clave' => 'serie', 'etiqueta' => 'Serie', 'obligatorio' => true],
            ['tipo' => 'texto', 'clave' => 'capacidad', 'etiqueta' => 'Capacidad (BTU o toneladas)', 'obligatorio' => false],
            ['tipo' => 'texto', 'clave' => 'refrigerante_tipo', 'etiqueta' => 'Refrigerante cargado (tipo)', 'obligatorio' => true],
            ['tipo' => 'numero', 'clave' => 'refrigerante_cantidad', 'unidad' => 'kg', 'etiqueta' => 'Refrigerante agregado / cargado en la instalación', 'obligatorio' => true],
            ['tipo' => 'texto', 'clave' => 'ubicacion_exacta', 'etiqueta' => 'Ubicación exacta', 'obligatorio' => true],
            ['tipo' => 'seleccion', 'clave' => 'tipo_unidad', 'etiqueta' => 'Unidad', 'opciones' => ['Interior', 'Exterior'], 'obligatorio' => false],

            $this->rev('soportes_base', 'Soportes y base nivelados y firmes'),
            $this->rev('tuberia_aislamiento', 'Tubería de refrigerante y aislamiento'),
            $this->rev('conexiones_electricas', 'Conexiones eléctricas y protección'),
            $this->rev('cable_calibre', 'Cable y calibre según la carga'),
            $this->rev('drenaje_pendiente', 'Drenaje con pendiente y prueba de agua'),
            $this->rev('ubicacion_espacios', 'Ubicación y espacios de servicio'),
            $this->rev('vacio_hermeticidad', 'Vacío y prueba de hermeticidad'),
            $this->rev('carga_registrada', 'Carga de refrigerante registrada'),
            $this->rev('control_funciones', 'Control remoto y funciones'),
            $this->rev('limpieza_area', 'Limpieza del área'),

            $this->num('voltaje_compresor', 'Prueba final · Voltaje', 'V'),
            $this->num('corriente_compresor', 'Prueba final · Amperaje del compresor', 'A'),
            $this->num('presion_succion', 'Prueba final · Presión de succión', 'psi'),
            $this->num('presion_descarga', 'Prueba final · Presión de descarga', 'psi'),
            $this->num('temp_suministro', 'Prueba final · Temperatura de suministro', '°C'),
            $this->num('temp_retorno', 'Prueba final · Temperatura de retorno', '°C'),

            ['tipo' => 'checklist', 'clave' => 'capacitacion_docs', 'etiqueta' => 'Capacitación y documentos entregados',
                'opciones' => ['Uso del control y modos', 'Limpieza básica de filtros', 'Calendario de mantenimiento', 'Certificado de garantía', 'Manual del equipo', 'Reporte con fotos'],
                'obligatorio' => false],
            ['tipo' => 'nota', 'clave' => 'pruebas_finales', 'etiqueta' => 'Pruebas finales y observaciones', 'obligatorio' => true],
            ['tipo' => 'foto', 'clave' => 'foto_instalacion', 'etiqueta' => 'Fotos de la instalación', 'obligatorio' => false],
            ['tipo' => 'firma', 'clave' => 'firma_tecnico', 'etiqueta' => 'Firma del técnico', 'obligatorio' => true],
            ['tipo' => 'firma', 'clave' => 'firma_cliente', 'etiqueta' => 'Cliente · recibe conforme', 'obligatorio' => true],
        ];
    }
};
