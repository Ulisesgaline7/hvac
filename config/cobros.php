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
];
