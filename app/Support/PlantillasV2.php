<?php

namespace App\Support;

/** Definiciones v2 de las plantillas (formato físico). Las usa el seeder; las migraciones 8002/8003 llevan su copia histórica. */
final class PlantillasV2
{
    private const ESTADO = ['OK', 'Con observación', 'N/A'];
    private const RESULTADO = ['Opera bien', 'Opera con observaciones', 'Requiere reparación'];

    public static function todas(): array
    {
        $d = self::definiciones();
        $d['instalacion'] = self::instalacion();

        return $d;
    }

    public static function med(string $clave, string $etiqueta, string $unidad, bool $obligatorio = false): array
    {
        return [
            ['tipo' => 'numero', 'clave' => $clave . '_antes', 'unidad' => $unidad, 'etiqueta' => "$etiqueta · antes", 'obligatorio' => false],
            ['tipo' => 'numero', 'clave' => $clave, 'unidad' => $unidad, 'etiqueta' => "$etiqueta · después", 'obligatorio' => $obligatorio],
        ];
    }

    public static function rev(string $clave, string $etiqueta): array
    {
        return ['tipo' => 'seleccion', 'clave' => $clave, 'etiqueta' => $etiqueta, 'opciones' => self::ESTADO, 'obligatorio' => false];
    }

    public static function cierre(): array
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

    public static function definiciones(): array
    {
        $aire = array_merge(
            [
                ['tipo' => 'seleccion', 'clave' => 'tipo_unidad', 'etiqueta' => 'Tipo de unidad', 'opciones' => ['Split', 'Mini split', 'Cassette', 'Piso techo', 'Paquete'], 'obligatorio' => false],
                ['tipo' => 'texto', 'clave' => 'motivo_visita', 'etiqueta' => 'Motivo de la visita', 'obligatorio' => true],
                ['tipo' => 'nota', 'clave' => 'estado_inicial', 'etiqueta' => 'Estado inicial del equipo', 'obligatorio' => false],
            ],
            self::med('voltaje_compresor', 'Voltaje de alimentación', 'V'),
            self::med('corriente_compresor', 'Amperaje del compresor', 'A'),
            self::med('amp_vent_exterior', 'Amperaje ventilador exterior', 'A'),
            self::med('amp_vent_interior', 'Amperaje ventilador interior', 'A'),
            self::med('presion_succion', 'Presión de succión', 'psi'),
            self::med('presion_descarga', 'Presión de descarga', 'psi'),
            self::med('temp_retorno', 'Temperatura de retorno', '°C', true),
            self::med('temp_suministro', 'Temperatura de suministro', '°C', true),
            self::med('sobrecalentamiento', 'Sobrecalentamiento', 'K'),
            self::med('subenfriamiento', 'Subenfriamiento', 'K'),
            [
                self::rev('filtros_limpios', 'Unidad interior · Filtros: limpieza o cambio'),
                self::rev('serpentin_evaporador', 'Unidad interior · Serpentín evaporador: limpieza y aletas'),
                self::rev('drenajes_ok', 'Unidad interior · Bandeja y drenaje de condensados'),
                self::rev('ventiladores_ok', 'Unidad interior · Ventilador o turbina'),
                self::rev('sensores', 'Unidad interior · Sensores y termistores'),
                self::rev('tarjeta_electronica', 'Unidad interior · Tarjeta electrónica y conexiones'),
                self::rev('aislamiento_interior', 'Unidad interior · Aislamiento y tuberías interiores'),
                self::rev('serpentin_condensador', 'Unidad exterior · Serpentín condensador: limpieza'),
                self::rev('ventilador_motor_ext', 'Unidad exterior · Ventilador y motor'),
                self::rev('compresor_estado', 'Unidad exterior · Compresor: ruido y temperatura'),
                self::rev('capacitores', 'Unidad exterior · Capacitores'),
                self::rev('contactor_reles', 'Unidad exterior · Contactor y relés'),
                self::rev('base_soportes', 'Unidad exterior · Base, soportes y vibración'),
                self::rev('tuberias_exteriores', 'Unidad exterior · Tuberías y aislamiento exteriores'),
                self::rev('conexiones_ok', 'Eléctrico · Cableado, terminales y apriete'),
                self::rev('proteccion_breaker', 'Eléctrico · Protección y breaker'),
                self::rev('conexion_tierra', 'Eléctrico · Conexión a tierra'),
                self::rev('control_remoto', 'Funcionamiento · Control remoto y modos'),
                self::rev('ruidos_anormales', 'Funcionamiento · Ruidos anormales'),
                self::rev('fugas_refrigerante', 'Funcionamiento · Fugas de refrigerante'),
                self::rev('enfriamiento_estable', 'Funcionamiento · Enfriamiento y estabilidad'),
            ],
            self::cierre()
        );

        $frio = array_merge(
            [
                ['tipo' => 'seleccion', 'clave' => 'tipo_camara', 'etiqueta' => 'Tipo de cámara', 'opciones' => ['Cuarto frío (conservación)', 'Congelador'], 'obligatorio' => false],
                ['tipo' => 'texto', 'clave' => 'motivo_visita', 'etiqueta' => 'Motivo de la visita', 'obligatorio' => true],
                ['tipo' => 'numero', 'clave' => 'temp_objetivo', 'unidad' => '°C', 'etiqueta' => 'Temperatura objetivo', 'obligatorio' => false],
                ['tipo' => 'numero', 'clave' => 'temp_real', 'unidad' => '°C', 'etiqueta' => 'Temperatura real', 'obligatorio' => false],
            ],
            self::med('temp_camara', 'Temperatura de la cámara', '°C'),
            self::med('temp_evaporacion', 'Temperatura de evaporación', '°C'),
            self::med('temp_condensacion', 'Temperatura de condensación', '°C'),
            self::med('presion_succion', 'Presión de succión', 'psi'),
            self::med('presion_descarga', 'Presión de descarga', 'psi'),
            self::med('sobrecalentamiento', 'Sobrecalentamiento', 'K'),
            self::med('subenfriamiento', 'Subenfriamiento', 'K'),
            self::med('corriente_compresor', 'Amperaje del compresor', 'A'),
            self::med('amp_vent_evaporador', 'Amperaje ventiladores del evaporador', 'A'),
            self::med('voltaje_compresor', 'Voltaje', 'V'),
            [
                self::rev('evaporador_aletas', 'Evaporador: limpieza y estado de aletas'),
                self::rev('ventiladores_evaporador', 'Ventiladores del evaporador'),
                self::rev('condensador_limpieza', 'Condensador: limpieza y ventilación'),
                self::rev('compresor_estado', 'Compresor: ruido, temperatura y nivel de aceite'),
                self::rev('ciclo_deshielo', 'Ciclo de deshielo y su programación'),
                self::rev('resistencias_deshielo', 'Resistencias de deshielo'),
                self::rev('valvula_expansion', 'Válvula de expansión y visor de líquido'),
                self::rev('fugas_refrigerante', 'Fugas de refrigerante'),
                self::rev('empaques_puertas', 'Empaques y cierres de puertas'),
                self::rev('cortinas', 'Cortinas de aire o de tiras'),
                self::rev('drenaje_resistencia', 'Drenaje y resistencia de drenaje'),
                self::rev('paneles_aislamiento', 'Paneles y aislamiento'),
                self::rev('tuberias_aislamiento', 'Tuberías y su aislamiento'),
                self::rev('controlador_alarmas', 'Controlador, termostato y alarmas'),
                self::rev('iluminacion', 'Iluminación'),
                self::rev('conexiones_ok', 'Conexiones eléctricas y protecciones'),
            ],
            self::cierre()
        );

        $comercial = array_merge(
            [
                ['tipo' => 'seleccion', 'clave' => 'tipo_equipo_comercial', 'etiqueta' => 'Tipo de equipo', 'opciones' => ['Mesa fría', 'Vitrina', 'Refrigerador', 'Máquina de hielo', 'Cámara de conservación'], 'obligatorio' => false],
                ['tipo' => 'texto', 'clave' => 'motivo_visita', 'etiqueta' => 'Motivo de la visita', 'obligatorio' => true],
            ],
            self::med('temp_producto', 'Temperatura del producto', '°C'),
            self::med('temp_aire_interior', 'Temperatura del aire interior', '°C'),
            self::med('presion_succion', 'Presión de succión', 'psi'),
            self::med('presion_descarga', 'Presión de descarga', 'psi'),
            self::med('corriente_compresor', 'Amperaje del compresor', 'A'),
            self::med('voltaje_compresor', 'Voltaje', 'V'),
            [
                self::rev('nivelacion', 'Nivelación y estabilidad del equipo'),
                self::rev('empaques_bisagras', 'Empaques y bisagras de puertas'),
                self::rev('limpieza_serpentines', 'Evaporador y condensador: limpieza'),
                self::rev('ventiladores_ok', 'Ventiladores y motores'),
                self::rev('compresor_arranque', 'Compresor y arranque'),
                self::rev('termostato_controlador', 'Termostato y controlador'),
                self::rev('ciclo_deshielo', 'Ciclo de deshielo'),
                self::rev('drenajes_ok', 'Drenaje y bandeja de condensados'),
                self::rev('fugas_refrigerante', 'Fugas de refrigerante'),
                self::rev('iluminacion', 'Iluminación'),
                self::rev('conexiones_ok', 'Conexiones eléctricas y cable'),
                self::rev('filtro_agua_valvula', 'Filtro de agua y válvula (máquinas de hielo)'),
                self::rev('calidad_hielo', 'Calidad y limpieza del hielo (máquinas de hielo)'),
            ],
            self::cierre()
        );

        return [
            'aire_acondicionado' => $aire,
            'cuartos_frios' => $frio,
            'refrigeracion_comercial' => $comercial,
        ];
    }

    public static function num(string $clave, string $etiqueta, string $unidad): array
    {
        return ['tipo' => 'numero', 'clave' => $clave, 'unidad' => $unidad, 'etiqueta' => $etiqueta, 'obligatorio' => false];
    }

    public static function instalacion(): array
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

            self::rev('soportes_base', 'Soportes y base nivelados y firmes'),
            self::rev('tuberia_aislamiento', 'Tubería de refrigerante y aislamiento'),
            self::rev('conexiones_electricas', 'Conexiones eléctricas y protección'),
            self::rev('cable_calibre', 'Cable y calibre según la carga'),
            self::rev('drenaje_pendiente', 'Drenaje con pendiente y prueba de agua'),
            self::rev('ubicacion_espacios', 'Ubicación y espacios de servicio'),
            self::rev('vacio_hermeticidad', 'Vacío y prueba de hermeticidad'),
            self::rev('carga_registrada', 'Carga de refrigerante registrada'),
            self::rev('control_funciones', 'Control remoto y funciones'),
            self::rev('limpieza_area', 'Limpieza del área'),

            self::num('voltaje_compresor', 'Prueba final · Voltaje', 'V'),
            self::num('corriente_compresor', 'Prueba final · Amperaje del compresor', 'A'),
            self::num('presion_succion', 'Prueba final · Presión de succión', 'psi'),
            self::num('presion_descarga', 'Prueba final · Presión de descarga', 'psi'),
            self::num('temp_suministro', 'Prueba final · Temperatura de suministro', '°C'),
            self::num('temp_retorno', 'Prueba final · Temperatura de retorno', '°C'),

            ['tipo' => 'checklist', 'clave' => 'capacitacion_docs', 'etiqueta' => 'Capacitación y documentos entregados',
                'opciones' => ['Uso del control y modos', 'Limpieza básica de filtros', 'Calendario de mantenimiento', 'Certificado de garantía', 'Manual del equipo', 'Reporte con fotos'],
                'obligatorio' => false],
            ['tipo' => 'nota', 'clave' => 'pruebas_finales', 'etiqueta' => 'Pruebas finales y observaciones', 'obligatorio' => true],
            ['tipo' => 'foto', 'clave' => 'foto_instalacion', 'etiqueta' => 'Fotos de la instalación', 'obligatorio' => false],
            ['tipo' => 'firma', 'clave' => 'firma_tecnico', 'etiqueta' => 'Firma del técnico', 'obligatorio' => true],
            ['tipo' => 'firma', 'clave' => 'firma_cliente', 'etiqueta' => 'Cliente · recibe conforme', 'obligatorio' => true],
        ];
    }
}
