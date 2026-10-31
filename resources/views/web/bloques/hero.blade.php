@php
    $b1 = \App\Support\SitioWeb::enlace($d['boton1_url'] ?? null);
    $b2 = \App\Support\SitioWeb::enlace($d['boton2_url'] ?? null);
    $img = \App\Support\SitioWeb::urlImagen($d['imagen'] ?? null, 'brand/control-total-simbolo.svg');
@endphp
<section class="hero">
    <div class="wrap">
        <div>
            <h1>{{ $d['titulo'] ?? '' }}</h1>
            @if(filled($d['texto'] ?? null))<p>{{ $d['texto'] }}</p>@endif
            @if(($b1 && filled($d['boton1_texto'] ?? null)) || ($b2 && filled($d['boton2_texto'] ?? null)))
                <div class="cta">
                    @if($b1 && filled($d['boton1_texto'] ?? null))<a class="btn btn-c" href="{{ $b1 }}">{{ $d['boton1_texto'] }}</a>@endif
                    @if($b2 && filled($d['boton2_texto'] ?? null))<a class="btn btn-o" href="{{ $b2 }}">{{ $d['boton2_texto'] }}</a>@endif
                </div>
            @endif
        </div>
        <img class="dial" src="{{ $img }}" alt="" width="380" height="380">
    </div>
</section>
