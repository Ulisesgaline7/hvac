@php
    $fotos = collect($d['imagenes'] ?? [])->values()->filter(fn ($f) => filled($f['imagen'] ?? null));
    $cols = in_array((string) ($d['columnas'] ?? '3'), ['2', '3', '4'], true) ? (int) $d['columnas'] : 3;
@endphp
@if($fotos->isNotEmpty())
<section {{ \App\Support\SitioWeb::seccion($d) }}>
    <div class="wrap">
        @if(filled($d['titulo'] ?? null))<h2>{{ $d['titulo'] }}</h2>@endif
        @if(filled($d['intro'] ?? null))<p class="lead">{{ $d['intro'] }}</p>@endif
        <div class="galeria" style="--cols: {{ $cols }}">
            @foreach($fotos as $f)
                @php $url = \App\Support\SitioWeb::urlImagen($f['imagen']); @endphp
                <figure>
                    <a href="{{ $url }}" target="_blank" rel="noopener"><img src="{{ $url }}" alt="{{ $f['pie'] ?? '' }}" loading="lazy"></a>
                    @if(filled($f['pie'] ?? null))<figcaption>{{ $f['pie'] }}</figcaption>@endif
                </figure>
            @endforeach
        </div>
    </div>
</section>
@endif
