@include('web.temas.aircon.partials.carrusel', ['slides' => [[
    'titulo' => $d['titulo'] ?? '', 'texto' => $d['texto'] ?? null, 'imagen' => $d['imagen'] ?? null,
    'boton_texto' => $d['boton1_texto'] ?? null, 'boton_url' => $d['boton1_url'] ?? null,
    'boton2_texto' => $d['boton2_texto'] ?? null, 'boton2_url' => $d['boton2_url'] ?? null,
]]])
