<?php

// Cláusulas generales del contrato. Reemplazar por el texto aprobado por el abogado.
return [
    // Representante legal de la Empresa (se imprime en el PDF).
    'representante' => ['nombre' => env('EMPRESA_REP_NOMBRE', 'Fredy Ulises Galindo Lainez'), 'cargo' => env('EMPRESA_REP_CARGO', 'Representante legal')],
    'clausulas' => [
        ['titulo' => 'Objeto', 'texto' => 'La Empresa prestará al Cliente los servicios descritos en este contrato sobre los equipos listados.'],
        ['titulo' => 'Acceso', 'texto' => 'El Cliente facilitará el acceso a las instalaciones y equipos en las fechas coordinadas.'],
        ['titulo' => 'Aceptación electrónica', 'texto' => 'El Cliente acepta firmar de forma electrónica. Se registran la fecha, la dirección IP y la huella (SHA-256) del documento firmado.'],
    ],
];
