@php $boton = \App\Support\SitioWeb::enlace($d['boton_url'] ?? null); @endphp
<section class="sec band">
    <div class="wrap cta-banda">
        @if(filled($d['titulo'] ?? null))<h2>{{ $d['titulo'] }}</h2>@endif
        @if(filled($d['texto'] ?? null))<p class="lead">{{ $d['texto'] }}</p>@endif
        @if($boton && filled($d['boton_texto'] ?? null))
            <div class="cta"><a class="btn btn-c" href="{{ $boton }}">{{ $d['boton_texto'] }}</a></div>
        @endif
    </div>
</section>
