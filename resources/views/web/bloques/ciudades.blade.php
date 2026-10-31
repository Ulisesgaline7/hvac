@php
    $aj = \App\Support\SitioWeb::ajustes();
    $ciudades = collect(explode(',', $aj['ciudades'] ?? ''))->map(fn ($c) => trim($c))->filter()->values()->all();
@endphp
@if(count($ciudades))
<section {{ \App\Support\SitioWeb::seccion($d) }}>
    <div class="wrap">
        @if(filled($d['titulo'] ?? null))<h2>{{ $d['titulo'] }}</h2>@endif
        @if(filled($d['intro'] ?? null))<p class="lead">{{ $d['intro'] }}</p>@endif
        <div class="chips">@foreach($ciudades as $c)<span>{{ $c }}</span>@endforeach</div>
    </div>
</section>
@endif
