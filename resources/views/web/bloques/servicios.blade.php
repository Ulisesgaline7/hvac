@php $servicios = \App\Models\ServicioWeb::where('visible', true)->orderBy('orden')->orderBy('id')->get(); @endphp
<section {{ \App\Support\SitioWeb::seccion($d) }}>
    <div class="wrap">
        @if(filled($d['titulo'] ?? null))<h2>{{ $d['titulo'] }}</h2>@endif
        @if(filled($d['intro'] ?? null))<p class="lead">{{ $d['intro'] }}</p>@endif
        <div class="grid">
            @forelse($servicios as $s)
                <article class="svc">
                    @if($s->imagen)<img class="svc-img" src="{{ asset('storage/' . $s->imagen) }}" alt="" loading="lazy">@endif
                    <h3>{{ $s->nombre }}</h3>
                    <p>{{ $s->descripcion }}</p>
                    @if($s->precio_desde !== null)
                        <p class="precio">Desde <strong>{{ $s->moneda }} {{ number_format((float) $s->precio_desde, 2) }}</strong>@if($s->unidad) <span>{{ $s->unidad }}</span>@endif</p>
                    @endif
                </article>
            @empty
                <p>Pronto publicaremos nuestros servicios.</p>
            @endforelse
        </div>
    </div>
</section>
