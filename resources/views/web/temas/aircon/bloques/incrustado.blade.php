@if(filled($d['codigo'] ?? null))
<div {{ \App\Support\SitioWeb::seccionAircon($d) }}>
    <div class="container">
        @if(filled($d['titulo'] ?? null))<h1 class="display-6 mb-4 text-center">{{ $d['titulo'] }}</h1>@endif
        <div class="contenido-embed text-center">{!! $d['codigo'] !!}</div>
    </div>
</div>
@endif
