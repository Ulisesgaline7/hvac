<?php

/** Reglas comerciales de Control Total. Fuente: Tarifario rev. 2 (14/10/2026) y Tarifario y Márgenes. */
return [
    'isv' => 0.15,
    'servicio_minimo' => 1500,
    'minimo_si_menos_de' => 3,
    'viaticos_dia' => 600,
    // Movilización y viáticos detallados (propuesta). 0 = desactivado, 1 = activado. Se editan en el panel.
    'viaticos_detallados' => 0,
    'km_tarifa' => 12,
    'alimentacion_dia' => 350,
    'hospedaje_noche' => 900,
    'validez_dias_habiles' => 5,

    // Recargos sobre el servicio. Por defecto se usa el mínimo; se puede indicar otro dentro del rango.
    'recargos' => [
        'ALTURA' => ['nombre' => 'Trabajo en altura mayor a 3 m', 'min' => 0.15, 'max' => 0.15],
        'ACCESO_DIFICIL' => ['nombre' => 'Acceso difícil (azotea, cielo falso cerrado, espacio confinado)', 'min' => 0.10, 'max' => 0.10],
        'NOCTURNO_DOMINICAL' => ['nombre' => 'Horario nocturno (6 pm a 6 am) o dominical', 'min' => 0.30, 'max' => 0.50],
        'EMERGENCIA_24_7' => ['nombre' => 'Emergencia 24/7 con respuesta en menos de 4 horas', 'min' => 0.50, 'max' => 0.50],
        'AREA_CONTROLADA' => ['nombre' => 'Área controlada (farmacéuticas, hospitales, salas limpias)', 'min' => 0.10, 'max' => 0.15],
    ],

    // No son acumulables: se aplica el mayor. «desde» = equipos mínimos para el descuento por volumen.
    'descuentos' => [
        'VOL_10_19' => ['nombre' => '10 a 19 equipos en una misma visita o sede', 'min' => 0.05, 'max' => 0.05, 'desde' => 10],
        'VOL_20_39' => ['nombre' => '20 a 39 equipos', 'min' => 0.08, 'max' => 0.08, 'desde' => 20],
        'VOL_40' => ['nombre' => '40 equipos o más', 'min' => 0.10, 'max' => 0.10, 'desde' => 40],
        'CONTRATO_2' => ['nombre' => 'Contrato anual (2 visitas por equipo)', 'min' => 0.08, 'max' => 0.08],
        'CONTRATO_4' => ['nombre' => 'Contrato anual (4 visitas por equipo)', 'min' => 0.12, 'max' => 0.15],
        'PLAN_ESENCIAL' => ['nombre' => 'Plan Esencial (4 visitas al año, cada 3 meses)', 'min' => 0.10, 'max' => 0.10],
        'PLAN_EMPRESARIAL' => ['nombre' => 'Plan Empresarial (6 visitas al año, cada 2 meses)', 'min' => 0.12, 'max' => 0.12],
        'PLAN_PREMIUM' => ['nombre' => 'Plan Premium (12 visitas al año, mensual)', 'min' => 0.15, 'max' => 0.15],
    ],

    // Garantía de mano de obra por categoría (hoja Garantía del Excel).
    'categorias' => [
        'preventivo' => ['dias' => 30, 'corrige' => 'El técnico que hizo el trabajo, sin costo', 'cubre' => 'Fallas por limpieza o revisión mal hechas', 'no_cubre' => 'Piezas gastadas, falta de gas, mal uso'],
        'instalacion' => ['dias' => 90, 'corrige' => 'El técnico que instaló, sin costo', 'cubre' => 'Fugas, drenaje, conexiones y soportes de la instalación', 'no_cubre' => 'Variaciones de voltaje, golpes, mal uso, la garantía del equipo (es del fabricante)'],
        'correctivo' => ['dias' => 30, 'corrige' => 'El técnico que reparó, sin costo', 'cubre' => 'La reparación realizada y su mano de obra', 'no_cubre' => 'Otras piezas no intervenidas, repuestos (garantía del fabricante)'],
        'refrigeracion_comercial' => ['dias' => 30, 'corrige' => 'El técnico del servicio, sin costo', 'cubre' => 'El trabajo realizado en el equipo', 'no_cubre' => 'Producto almacenado, mal uso, fallas ajenas al trabajo'],
        'repuestos_gas' => ['dias' => 90, 'corrige' => 'Se gestiona con el proveedor o el fabricante', 'cubre' => 'Defecto de fábrica del repuesto', 'no_cubre' => 'Instalación por terceros, mal uso'],
        'administracion' => ['dias' => 30, 'corrige' => 'El técnico asignado, sin costo', 'cubre' => 'El trabajo ejecutado', 'no_cubre' => 'Lo que no fue parte del trabajo'],
    ],

    // Texto base de la cotización (Tarifario, sección 11).
    'condiciones' => [
        'Precios en Lempiras, sin ISV (15 %) salvo que se indique lo contrario.',
        'Cotización válida por 5 días hábiles.',
        'Forma de pago: 50 % al inicio del servicio y 50 % al finalizar. Clientes con crédito aprobado: 15 / 30 días.',
        'Viáticos y transporte por trabajos fuera de Tegucigalpa y Comayagüela se facturan aparte.',
        'Garantía de mano de obra: 30 días (hasta 90 en instalaciones). Repuestos según garantía del fabricante. Excluye daños por variaciones de voltaje, falta de mantenimiento o limpieza previa y mal uso.',
        'Esta cotización no incluye repuestos ni gas refrigerante. Si el equipo los requiere se informará y cotizará antes de ejecutar.',
        'Si el cliente no aprueba la reparación o los repuestos requeridos, solo procede el cobro del diagnóstico.',
        'El tiempo de entrega se cuenta desde la aceptación firmada y, en su caso, el anticipo.',
        'El cliente debe facilitar acceso, energía eléctrica y áreas libres para trabajar.',
        'Horario normal: lunes a viernes 8:00 am a 5:00 pm y sábados 8:00 am a 12:00 m.',
    ],
];
