@php
    $fotos = collect($d['imagenes'] ?? [])->values()->filter(fn ($f) => filled($f['imagen'] ?? null));
    $cols = (string) ($d['columnas'] ?? '3');
    $colClase = ['2' => 'col-6 col-md-6', '3' => 'col-6 col-md-4', '4' => 'col-6 col-md-3'][$cols] ?? 'col-6 col-md-4';
@endphp
@if($fotos->isNotEmpty())
<div {{ \App\Support\SitioWeb::seccionAircon($d) }}>
    <div class="container">
        <div class="text-center mx-auto wow fadeInUp" data-wow-delay="0.1s" style="max-width: 640px;">
            @if(filled($d['titulo'] ?? null))<h1 @class(['display-6', 'mb-3' => filled($d['intro'] ?? null), 'mb-5' => ! filled($d['intro'] ?? null)])>{{ $d['titulo'] }}</h1>@endif
            @if(filled($d['intro'] ?? null))<p class="mb-5">{{ $d['intro'] }}</p>@endif
        </div>
        <div class="row g-3">
            @foreach($fotos as $f)
                @php $url = \App\Support\SitioWeb::urlImagen($f['imagen']); @endphp
                <div class="{{ $colClase }} wow zoomIn" data-wow-delay="{{ 0.1 + (($loop->index % 4) * 0.1) }}s">
                    <a href="{{ $url }}" target="_blank" rel="noopener">
                        <img class="img-fluid rounded w-100" style="height: 230px; object-fit: cover;" src="{{ $url }}" alt="{{ $f['pie'] ?? '' }}" loading="lazy">
                    </a>
                    @if(filled($f['pie'] ?? null))<small class="d-block text-muted mt-1">{{ $f['pie'] }}</small>@endif
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif
