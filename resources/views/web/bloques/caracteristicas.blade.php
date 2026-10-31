<section {{ \App\Support\SitioWeb::seccion($d) }}>
    <div class="wrap">
        @if(filled($d['titulo'] ?? null))<h2>{{ $d['titulo'] }}</h2>@endif
        @if(filled($d['intro'] ?? null))<p class="lead">{{ $d['intro'] }}</p>@endif
        <div class="feat">
            @foreach(collect($d['items'] ?? [])->values() as $i)
                <div><h3>{{ $i['titulo'] ?? '' }}</h3><p>{{ $i['texto'] ?? '' }}</p></div>
            @endforeach
        </div>
    </div>
</section>
