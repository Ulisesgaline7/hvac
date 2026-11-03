<?php

/*
 * SLA de órdenes de trabajo (en horas, contadas desde la creación de la OT).
 *
 * atencion: tiempo máximo para que el técnico llegue (check-in).
 * cierre:   tiempo máximo para cerrar la OT.
 *
 * Aplica a correctivo, emergencia y visita técnica. Las preventivas y
 * las instalaciones se miden contra su fecha programada.
 * Ajusta estos valores a lo que ofrecen a sus clientes.
 */
return [
    'prioridades' => [
        'emergencia' => ['atencion' => 2,  'cierre' => 24],
        'alta'       => ['atencion' => 8,  'cierre' => 48],
        'normal'     => ['atencion' => 24, 'cierre' => 120],
        'baja'       => ['atencion' => 72, 'cierre' => 240],
    ],

    // Duración que se asume cuando la OT no tiene duración estimada.
    'duracion_default_min' => 120,

    // A partir de cuántas OT en un día se avisa de carga alta del técnico.
    'max_ot_por_dia' => 4,
];
