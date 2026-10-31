@php $s = collect($d['slides'] ?? [])->values()->first() ?? []; @endphp
@if(filled($s['titulo'] ?? null))
    @include('web.bloques.hero', ['d' => [
        'titulo' => $s['titulo'], 'texto' => $s['texto'] ?? null, 'imagen' => $s['imagen'] ?? null,
        'boton1_texto' => $s['boton_texto'] ?? null, 'boton1_url' => $s['boton_url'] ?? null,
    ]])
@endif
