<?php

return [
    'nombre' => env('EMPRESA_REP_NOMBRE', 'Fredy Ulises Galindo Lainez'),
    'cargo' => env('EMPRESA_REP_CARGO', 'Representante legal'),

    // Qué PDF llevan la firma y el sello de la empresa. Apagar uno = false.
    'certificar_entregables' => env('CERTIFICAR_ENTREGABLES', true),

    'documentos' => [
        'cotizacion' => true,
        'contrato' => true,
        'acta' => false,
        'informe_ot' => false,
        'garantia' => ['administrador', 'sello'],
        'capacitacion' => ['tecnico', 'supervisor', 'sello'],
        'protocolo' => ['tecnico', 'supervisor', 'sello'],
        'calendario' => [],
        'factura' => false,
        'recibo' => false,
        'liquidacion' => false,
    ],
];
