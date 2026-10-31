@php $tarjeta = ($d['fondo'] ?? 'claro') === 'banda' ? 'bg-white' : 'bg-light'; @endphp
<div {{ \App\Support\SitioWeb::seccionAircon($d) }}>
    <div class="container">
        <div class="text-center mx-auto wow fadeInUp" data-wow-delay="0.1s" style="max-width: 640px;">
            @if(filled($d['titulo'] ?? null))<h1 @class(['display-6', 'mb-3' => filled($d['intro'] ?? null), 'mb-5' => ! filled($d['intro'] ?? null)])>{{ $d['titulo'] }}</h1>@endif
            @if(filled($d['intro'] ?? null))<p class="mb-5">{{ $d['intro'] }}</p>@endif
        </div>
        <div class="row g-4">
            @foreach(collect($d['items'] ?? [])->values() as $i)
                <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="{{ 0.1 + (($loop->index % 3) * 0.2) }}s">
                    <div class="{{ $tarjeta }} h-100 p-4" style="border-top: 4px solid var(--primary);">
                        <h5 class="mb-3">{{ $i['titulo'] ?? '' }}</h5>
                        <span>{{ $i['texto'] ?? '' }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
