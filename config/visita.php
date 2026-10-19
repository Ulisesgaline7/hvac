<?php

return [
    // Monto base de una visita técnica (ajústelo a su tarifa real).
    'costo' => 500.00,

    // Qué pasa con el costo según el trabajo que se apruebe después de la visita.
    // bonificar = se muestra y se descuenta | cobrar = se suma a la cotización
    'si_aprueba' => [
        'correctivo' => 'bonificar',
        'instalacion' => 'cobrar',
        'preventivo' => 'cobrar',
        'refrigeracion_comercial' => 'cobrar',
        'administracion' => 'cobrar',
    ],

    // Si el cliente no aprueba la cotización.
    'si_rechaza' => 'cobrar',
];
