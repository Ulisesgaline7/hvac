<div {{ \App\Support\SitioWeb::seccionAircon($d) }}>
    <div class="container">
        <div class="mx-auto contenido-rico wow fadeInUp" data-wow-delay="0.1s" style="max-width: 820px;">
            @if(filled($d['titulo'] ?? null))<h1 class="display-6 mb-4">{{ $d['titulo'] }}</h1>@endif
            {!! $d['contenido'] ?? '' !!}
        </div>
    </div>
</div>
