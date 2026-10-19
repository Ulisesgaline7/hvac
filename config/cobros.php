<?php

return [
    // % de anticipo sugerido por tipo de OT.
    'anticipo_pct' => [
        'instalacion' => 50,
        'correctivo' => 0,
        'emergencia' => 0,
    ],

    // Días de crédito por defecto (0 = pago contra entrega).
    'dias_credito' => 0,

    // Tipos de OT que generan factura al cerrarse.
    // Preventivo (se cobra por contrato) y visita técnica (se cobra vía cotización) quedan fuera por ahora.
    'facturan_al_cerrar' => ['instalacion', 'correctivo', 'emergencia'],

    // Si la OT no tiene cotización: ¿precio_estimado ya incluye ISV?
    'precio_ot_incluye_isv' => false,

    // Periodicidad de cobro de contratos preventivos (se usará al activar contratos).
    'contrato_periodicidad' => 'mensual',

    // true = los recibos y facturas llevan la leyenda «sin validez fiscal».
    // Pasar a false solo cuando exista autorización de numeración (CAI) del SAR.
    'documentos_prueba' => true,

    // Cuándo nace la factura:
    //   al_cerrar         = al cerrar la OT (comportamiento actual).
    //   al_pagar_completo = con crédito, al cerrar; sin crédito, cuando lo cobrado cubre el total.
    // Decisión pendiente de validar con el contador.
    'momento_factura' => 'al_cerrar',

    // Clase que asigna el número de factura. Debe implementar App\Services\Numeracion\NumeradorFactura.
    // Hoy: numeración interna de prueba. Con CAI del SAR se cambia por un numerador basado en el rango autorizado.
    'numerador' => \App\Services\Numeracion\NumeradorInterno::class,
];
