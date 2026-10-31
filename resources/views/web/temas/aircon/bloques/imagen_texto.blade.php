@php
    $img = \App\Support\SitioWeb::urlImagen($d['imagen'] ?? null);
    $boton = \App\Support\SitioWeb::enlace($d['boton_url'] ?? null);
    $inv = ($d['posicion'] ?? 'izquierda') === 'derecha';
@endphp
<div {{ \App\Support\SitioWeb::seccionAircon($d) }}>
    <div class="container">
        <div class="row g-5 align-items-center">
            @if($img)
                <div @class(['col-lg-6 wow fadeInUp', 'order-lg-2' => $inv]) data-wow-delay="0.1s">
                    <img class="img-fluid rounded w-100" src="{{ $img }}" alt="{{ $d['titulo'] ?? '' }}" loading="lazy">
                </div>
            @endif
            <div @class(['wow fadeInUp', 'col-lg-6' => (bool) $img, 'col-12' => ! $img]) data-wow-delay="0.3s">
                @if(filled($d['titulo'] ?? null))<h1 class="display-6 mb-4">{{ $d['titulo'] }}</h1>@endif
                <div class="mb-4 contenido-rico">{!! $d['contenido'] ?? '' !!}</div>
                @if($boton && filled($d['boton_texto'] ?? null))
                    <a class="btn btn-primary py-3 px-5" href="{{ $boton }}">{{ $d['boton_texto'] }}</a>
                @endif
            </div>
        </div>
    </div>
</div>
