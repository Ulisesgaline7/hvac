<section {{ \App\Support\SitioWeb::seccion($d) }}>
    <div class="wrap">
        <div class="pagina">
            @if(filled($d['titulo'] ?? null))<h2>{{ $d['titulo'] }}</h2>@endif
            <div class="pagina-contenido">{!! $d['contenido'] ?? '' !!}</div>
        </div>
    </div>
</section>
