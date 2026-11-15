<?php

/** Subdominio de cada panel. Vacío = ese panel sigue en su ruta de siempre (/admin, /portal, /tecnico). */
return [
    'admin' => env('DOMINIO_ADMIN') ?: null,
    'portal' => env('DOMINIO_PORTAL') ?: null,
    'tecnico' => env('DOMINIO_TECNICO') ?: null,
];
