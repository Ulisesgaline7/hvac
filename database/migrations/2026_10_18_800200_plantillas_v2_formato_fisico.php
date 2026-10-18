<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const ESTADO = ['OK', 'Con observación', 'N/A'];
    private const RESULTADO = ['Opera bien', 'Opera con observaciones', 'Requiere reparación'];

    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::select("select set_config('app.es_interno','true',false)");
        }

        foreach ($this->definiciones() as $familia => $campos) {
            $plantilla = DB::table('plantillas_formulario')->where('familia', $familia)->whereNull('deleted_at')->first();
            if (! $plantilla) {
                continue;
            }

            $ultima = (int) DB::table('plantilla_versiones')->where('plantilla_id', $plantilla->id)->max('version');

            DB::table('plantilla_versiones')->insert([
                'plantilla_id' => $plantilla->id,
                'version' => $ultima + 1,
                'definicion' => json_encode($campos, JSON_UNESCAPED_UNICODE),
            ]);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::select("select set_config('app.es_interno','true',false)");
        }

        // Solo borra la última versión de cada familia si ninguna ficha la usa.
        foreach (array_keys($this->definiciones()) as $familia) {
            $p = DB::table('plantillas_formulario')->where('familia', $familia)->first();
            if (! $p) {
                continue;
            }
            $v = DB::table('plantilla_versiones')->where('plantilla_id', $p->id)->orderByDesc('version')->first();
            if ($v && $v->version > 1 && ! DB::table('fichas_tecnicas')->where('plantilla_version_id', $v->id)->exists()) {
                DB::table('plantilla_versiones')->where('id', $v->id)->delete();
            }
        }
    }

    private function med(string $clave, string $etiqueta, string $unidad, bool $obligatorio = false): array
    {
        return [
            ['tipo' => 'numero', 'clave' => $clave . '_antes', 'unidad' => $unidad, 'etiqueta' => "$etiqueta · antes", 'obligatorio' => false],
            ['tipo' => 'numero', 'clave' => $clave, 'unidad' => $unidad, 'etiqueta' => "$etiqueta · después", 'obligatorio' => $obligatorio],
        ];
    }

    private function rev(string $clave, string $etiqueta): array
    {
        return ['tipo' => 'seleccion', 'clave' => $clave, 'etiqueta' => $etiqueta, 'opciones' => self::ESTADO, 'obligatorio' => false];
    }

    private function cierre(): array
    {
        return [
            ['tipo' => 'nota', 'clave' => 'obs_verificacion', 'etiqueta' => 'Observaciones de la verificación (indique el punto)', 'obligatorio' => false],
            ['tipo' => 'nota', 'clave' => 'trabajo_realizado', 'etiqueta' => 'Trabajo realizado', 'obligatorio' => true],
            ['tipo' => 'nota', 'clave' => 'repuestos_usados', 'etiqueta' => 'Repuestos y materiales usados', 'obligatorio' => false],
            ['tipo' => 'texto', 'clave' => 'refrigerante_tipo', 'etiqueta' => 'Refrigerante intervenido (tipo)', 'obligatorio' => false],
            ['tipo' => 'numero', 'clave' => 'refrigerante_agregado', 'unidad' => 'kg', 'etiqueta' => 'Refrigerante agregado', 'obligatorio' => false],
            ['tipo' => 'numero', 'clave' => 'refrigerante_recuperado', 'unidad' => 'kg', 'etiqueta' => 'Refrigerante recuperado', 'obligatorio' => false],
            ['tipo' => 'texto', 'clave' => 'causa_falla', 'etiqueta' => 'Causa de la falla', 'obligatorio' => false],
            ['tipo' => 'nota', 'clave' => 'pruebas_finales', 'etiqueta' => 'Pruebas finales', 'obligatorio' => false],
            ['tipo' => 'seleccion', 'clave' => 'resultado_servicio', 'etiqueta' => 'Resultado del servicio', 'opciones' => self::RESULTADO, 'obligatorio' => true],
            ['tipo' => 'nota', 'clave' => 'recomendaciones', 'etiqueta' => 'Observaciones y recomendaciones', 'obligatorio' => false],
            ['tipo' => 'foto', 'clave' => 'foto_antes', 'etiqueta' => 'Fotos antes', 'obligatorio' => false],
            ['tipo' => 'foto', 'clave' => 'foto_despues', 'etiqueta' => 'Fotos después', 'obligatorio' => false],
            ['tipo' => 'firma', 'clave' => 'firma_tecnico', 'etiqueta' => 'Firma del técnico', 'obligatorio' => true],
            ['tipo' => 'firma', 'clave' => 'firma_cliente', 'etiqueta' => 'Firma del cliente', 'obligatorio' => true],
        ];
    }

    private function definiciones(): array
    {
        $aire = array_merge(
            [
                ['tipo' => 'seleccion', 'clave' => 'tipo_unidad', 'etiqueta' => 'Tipo de unidad', 'opciones' => ['Split', 'Mini split', 'Cassette', 'Piso techo', 'Paquete'], 'obligatorio' => false],
                ['tipo' => 'texto', 'clave' => 'motivo_visita', 'etiqueta' => 'Motivo de la visita', 'obligatorio' => true],
                ['tipo' => 'nota', 'clave' => 'estado_inicial', 'etiqueta' => 'Estado inicial del equipo', 'obligatorio' => false],
            ],
            $this->med('voltaje_compresor', 'Voltaje de alimentación', 'V'),
            $this->med('corriente_compresor', 'Amperaje del compresor', 'A'),
            $this->med('amp_vent_exterior', 'Amperaje ventilador exterior', 'A'),
            $this->med('amp_vent_interior', 'Amperaje ventilador interior', 'A'),
            $this->med('presion_succion', 'Presión de succión', 'psi'),
            $this->med('presion_descarga', 'Presión de descarga', 'psi'),
            $this->med('temp_retorno', 'Temperatura de retorno', '°C', true),
            $this->med('temp_suministro', 'Temperatura de suministro', '°C', true),
            $this->med('sobrecalentamiento', 'Sobrecalentamiento', 'K'),
            $this->med('subenfriamiento', 'Subenfriamiento', 'K'),
            [
                $this->rev('filtros_limpios', 'Unidad interior · Filtros: limpieza o cambio'),
                $this->rev('serpentin_evaporador', 'Unidad interior · Serpentín evaporador: limpieza y aletas'),
                $this->rev('drenajes_ok', 'Unidad interior · Bandeja y drenaje de condensados'),
                $this->rev('ventiladores_ok', 'Unidad interior · Ventilador o turbina'),
                $this->rev('sensores', 'Unidad interior · Sensores y termistores'),
                $this->rev('tarjeta_electronica', 'Unidad interior · Tarjeta electrónica y conexiones'),
                $this->rev('aislamiento_interior', 'Unidad interior · Aislamiento y tuberías interiores'),
                $this->rev('serpentin_condensador', 'Unidad exterior · Serpentín condensador: limpieza'),
                $this->rev('ventilador_motor_ext', 'Unidad exterior · Ventilador y motor'),
                $this->rev('compresor_estado', 'Unidad exterior · Compresor: ruido y temperatura'),
                $this->rev('capacitores', 'Unidad exterior · Capacitores'),
                $this->rev('contactor_reles', 'Unidad exterior · Contactor y relés'),
                $this->rev('base_soportes', 'Unidad exterior · Base, soportes y vibración'),
                $this->rev('tuberias_exteriores', 'Unidad exterior · Tuberías y aislamiento exteriores'),
                $this->rev('conexiones_ok', 'Eléctrico · Cableado, terminales y apriete'),
                $this->rev('proteccion_breaker', 'Eléctrico · Protección y breaker'),
                $this->rev('conexion_tierra', 'Eléctrico · Conexión a tierra'),
                $this->rev('control_remoto', 'Funcionamiento · Control remoto y modos'),
                $this->rev('ruidos_anormales', 'Funcionamiento · Ruidos anormales'),
                $this->rev('fugas_refrigerante', 'Funcionamiento · Fugas de refrigerante'),
                $this->rev('enfriamiento_estable', 'Funcionamiento · Enfriamiento y estabilidad'),
            ],
            $this->cierre()
        );

        $frio = array_merge(
            [
                ['tipo' => 'seleccion', 'clave' => 'tipo_camara', 'etiqueta' => 'Tipo de cámara', 'opciones' => ['Cuarto frío (conservación)', 'Congelador'], 'obligatorio' => false],
                ['tipo' => 'texto', 'clave' => 'motivo_visita', 'etiqueta' => 'Motivo de la visita', 'obligatorio' => true],
                ['tipo' => 'numero', 'clave' => 'temp_objetivo', 'unidad' => '°C', 'etiqueta' => 'Temperatura objetivo', 'obligatorio' => false],
                ['tipo' => 'numero', 'clave' => 'temp_real', 'unidad' => '°C', 'etiqueta' => 'Temperatura real', 'obligatorio' => false],
            ],
            $this->med('temp_camara', 'Temperatura de la cámara', '°C'),
            $this->med('temp_evaporacion', 'Temperatura de evaporación', '°C'),
            $this->med('temp_condensacion', 'Temperatura de condensación', '°C'),
            $this->med('presion_succion', 'Presión de succión', 'psi'),
            $this->med('presion_descarga', 'Presión de descarga', 'psi'),
            $this->med('sobrecalentamiento', 'Sobrecalentamiento', 'K'),
            $this->med('subenfriamiento', 'Subenfriamiento', 'K'),
            $this->med('corriente_compresor', 'Amperaje del compresor', 'A'),
            $this->med('amp_vent_evaporador', 'Amperaje ventiladores del evaporador', 'A'),
            $this->med('voltaje_compresor', 'Voltaje', 'V'),
            [
                $this->rev('evaporador_aletas', 'Evaporador: limpieza y estado de aletas'),
                $this->rev('ventiladores_evaporador', 'Ventiladores del evaporador'),
                $this->rev('condensador_limpieza', 'Condensador: limpieza y ventilación'),
                $this->rev('compresor_estado', 'Compresor: ruido, temperatura y nivel de aceite'),
                $this->rev('ciclo_deshielo', 'Ciclo de deshielo y su programación'),
                $this->rev('resistencias_deshielo', 'Resistencias de deshielo'),
                $this->rev('valvula_expansion', 'Válvula de expansión y visor de líquido'),
                $this->rev('fugas_refrigerante', 'Fugas de refrigerante'),
                $this->rev('empaques_puertas', 'Empaques y cierres de puertas'),
                $this->rev('cortinas', 'Cortinas de aire o de tiras'),
                $this->rev('drenaje_resistencia', 'Drenaje y resistencia de drenaje'),
                $this->rev('paneles_aislamiento', 'Paneles y aislamiento'),
                $this->rev('tuberias_aislamiento', 'Tuberías y su aislamiento'),
                $this->rev('controlador_alarmas', 'Controlador, termostato y alarmas'),
                $this->rev('iluminacion', 'Iluminación'),
                $this->rev('conexiones_ok', 'Conexiones eléctricas y protecciones'),
            ],
            $this->cierre()
        );

        $comercial = array_merge(
            [
                ['tipo' => 'seleccion', 'clave' => 'tipo_equipo_comercial', 'etiqueta' => 'Tipo de equipo', 'opciones' => ['Mesa fría', 'Vitrina', 'Refrigerador', 'Máquina de hielo', 'Cámara de conservación'], 'obligatorio' => false],
                ['tipo' => 'texto', 'clave' => 'motivo_visita', 'etiqueta' => 'Motivo de la visita', 'obligatorio' => true],
            ],
            $this->med('temp_producto', 'Temperatura del producto', '°C'),
            $this->med('temp_aire_interior', 'Temperatura del aire interior', '°C'),
            $this->med('presion_succion', 'Presión de succión', 'psi'),
            $this->med('presion_descarga', 'Presión de descarga', 'psi'),
            $this->med('corriente_compresor', 'Amperaje del compresor', 'A'),
            $this->med('voltaje_compresor', 'Voltaje', 'V'),
            [
                $this->rev('nivelacion', 'Nivelación y estabilidad del equipo'),
                $this->rev('empaques_bisagras', 'Empaques y bisagras de puertas'),
                $this->rev('limpieza_serpentines', 'Evaporador y condensador: limpieza'),
                $this->rev('ventiladores_ok', 'Ventiladores y motores'),
                $this->rev('compresor_arranque', 'Compresor y arranque'),
                $this->rev('termostato_controlador', 'Termostato y controlador'),
                $this->rev('ciclo_deshielo', 'Ciclo de deshielo'),
                $this->rev('drenajes_ok', 'Drenaje y bandeja de condensados'),
                $this->rev('fugas_refrigerante', 'Fugas de refrigerante'),
                $this->rev('iluminacion', 'Iluminación'),
                $this->rev('conexiones_ok', 'Conexiones eléctricas y cable'),
                $this->rev('filtro_agua_valvula', 'Filtro de agua y válvula (máquinas de hielo)'),
                $this->rev('calidad_hielo', 'Calidad y limpieza del hielo (máquinas de hielo)'),
            ],
            $this->cierre()
        );

        return [
            'aire_acondicionado' => $aire,
            'cuartos_frios' => $frio,
            'refrigeracion_comercial' => $comercial,
        ];
    }
};
