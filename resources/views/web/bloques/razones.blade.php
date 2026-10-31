@include('web.bloques.caracteristicas', ['d' => [
    'ancla' => $d['ancla'] ?? null, 'fondo' => $d['fondo'] ?? 'banda',
    'titulo' => $d['titulo'] ?? null, 'intro' => $d['parrafo'] ?? null, 'items' => $d['items'] ?? [],
]])
