@php
    $img = \App\Support\SitioWeb::urlImagen($d['imagen'] ?? null);
    $boton = \App\Support\SitioWeb::enlace($d['boton_url'] ?? null);
@endphp
<section {{ \App\Support\SitioWeb::seccion($d) }}>
    <div class="wrap">
        @if($img)
            <div @class(['img-texto', 'inv' => ($d['posicion'] ?? 'izquierda') === 'derecha'])>
                <div class="img-texto-img"><img src="{{ $img }}" alt="{{ $d['titulo'] ?? '' }}" loading="lazy"></div>
                <div>
                    @if(filled($d['titulo'] ?? null))<h2>{{ $d['titulo'] }}</h2>@endif
                    <div class="pagina-contenido">{!! $d['contenido'] ?? '' !!}</div>
                    @if($boton && filled($d['boton_texto'] ?? null))<p><a class="btn btn-c" href="{{ $boton }}">{{ $d['boton_texto'] }}</a></p>@endif
                </div>
            </div>
        @else
            <div class="pagina">
                @if(filled($d['titulo'] ?? null))<h2>{{ $d['titulo'] }}</h2>@endif
                <div class="pagina-contenido">{!! $d['contenido'] ?? '' !!}</div>
                @if($boton && filled($d['boton_texto'] ?? null))<p><a class="btn btn-c" href="{{ $boton }}">{{ $d['boton_texto'] }}</a></p>@endif
            </div>
        @endif
    </div>
</section>
