@if(filled($d['codigo'] ?? null))
<section {{ \App\Support\SitioWeb::seccion($d) }}>
    <div class="wrap">
        @if(filled($d['titulo'] ?? null))<h2>{{ $d['titulo'] }}</h2>@endif
        <div class="embed">{!! $d['codigo'] !!}</div>
    </div>
</section>
@endif
