<section {{ \App\Support\SitioWeb::seccion($d) }}>
    <div class="wrap">
        @if(filled($d['titulo'] ?? null))<h2>{{ $d['titulo'] }}</h2>@endif
        <div class="faq">
            @foreach(collect($d['items'] ?? [])->values() as $i)
                <details>
                    <summary>{{ $i['pregunta'] ?? '' }}</summary>
                    <p>{!! nl2br(e($i['respuesta'] ?? '')) !!}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>
