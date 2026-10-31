@php $boton = \App\Support\SitioWeb::enlace($d['boton_url'] ?? null); @endphp
<div class="container-fluid bg-primary py-5 my-5">
    <div class="container text-center wow fadeInUp" data-wow-delay="0.1s">
        @if(filled($d['titulo'] ?? null))<h1 class="display-6 text-white mb-3">{{ $d['titulo'] }}</h1>@endif
        @if(filled($d['texto'] ?? null))<p class="text-white mb-4">{{ $d['texto'] }}</p>@endif
        @if($boton && filled($d['boton_texto'] ?? null))
            <a class="btn btn-secondary py-3 px-5" href="{{ $boton }}">{{ $d['boton_texto'] }}</a>
        @endif
    </div>
</div>
