@php
    $aj = \App\Support\SitioWeb::ajustes();
    $ciudades = collect(explode(',', $aj['ciudades'] ?? ''))->map(fn ($c) => trim($c))->filter()->values()->all();
@endphp
@if(count($ciudades))
<div {{ \App\Support\SitioWeb::seccionAircon($d) }}>
    <div class="container">
        <div class="text-center mx-auto wow fadeInUp" data-wow-delay="0.1s" style="max-width: 640px;">
            @if(filled($d['titulo'] ?? null))<h1 @class(['display-6', 'mb-3' => filled($d['intro'] ?? null), 'mb-5' => ! filled($d['intro'] ?? null)])>{{ $d['titulo'] }}</h1>@endif
            @if(filled($d['intro'] ?? null))<p class="mb-5">{{ $d['intro'] }}</p>@endif
        </div>
        <div class="text-center">
            @foreach($ciudades as $c)
                <span class="badge bg-white text-dark border fw-normal fs-6 px-3 py-2 me-2 mb-2">{{ $c }}</span>
            @endforeach
        </div>
    </div>
</div>
@endif
