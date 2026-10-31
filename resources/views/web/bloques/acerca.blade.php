@php
    $img = \App\Support\SitioWeb::urlImagen($d['imagen1'] ?? null);
    $destacados = collect($d['destacados'] ?? [])->values()->filter(fn ($x) => filled($x['texto'] ?? null));
@endphp
<section {{ \App\Support\SitioWeb::seccion($d) }}>
    <div class="wrap">
        @if($img)
            <div class="img-texto">
                <div class="img-texto-img"><img src="{{ $img }}" alt="{{ $d['titulo'] ?? '' }}" loading="lazy"></div>
                <div>
                    @if(filled($d['titulo'] ?? null))<h2>{{ $d['titulo'] }}</h2>@endif
                    @if($destacados->isNotEmpty())<ul>@foreach($destacados as $x)<li>{{ $x['texto'] }}</li>@endforeach</ul>@endif
                    <div class="pagina-contenido">{!! $d['parrafo'] ?? '' !!}</div>
                </div>
            </div>
        @else
            <div class="pagina">
                @if(filled($d['titulo'] ?? null))<h2>{{ $d['titulo'] }}</h2>@endif
                @if($destacados->isNotEmpty())<ul>@foreach($destacados as $x)<li>{{ $x['texto'] }}</li>@endforeach</ul>@endif
                <div class="pagina-contenido">{!! $d['parrafo'] ?? '' !!}</div>
            </div>
        @endif
    </div>
</section>
