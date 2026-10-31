@php
    $servicios = \App\Models\ServicioWeb::where('visible', true)->orderBy('orden')->orderBy('id')->get();
    $detalle = $d['mostrar_detalle'] ?? true;
@endphp
<!-- Service Start -->
<div {{ \App\Support\SitioWeb::seccionAircon($d) }}>
    <div class="container">
        <div class="text-center mx-auto wow fadeInUp" data-wow-delay="0.1s" style="max-width: 640px;">
            @if(filled($d['titulo'] ?? null))<h1 @class(['display-6', 'mb-3' => filled($d['intro'] ?? null), 'mb-5' => ! filled($d['intro'] ?? null)])>{{ $d['titulo'] }}</h1>@endif
            @if(filled($d['intro'] ?? null))<p class="mb-5">{{ $d['intro'] }}</p>@endif
        </div>
        <div class="row g-4 justify-content-center">
            @forelse($servicios as $i => $s)
                <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="{{ 0.1 + (($i % 3) * 0.2) }}s">
                    <div class="service-item h-100 bg-light">
                        <img class="img-fluid w-100" style="aspect-ratio: 11 / 8; object-fit: cover;" src="{{ \App\Support\SitioWeb::urlImagen($s->imagen, 'aircon/img/service-' . (($i % 6) + 1) . '.jpg') }}" alt="" loading="lazy">
                        <div class="d-flex align-items-center bg-light">
                            <div class="service-icon flex-shrink-0 bg-primary">
                                <img class="img-fluid" src="{{ \App\Support\SitioWeb::iconoAircon((string) (($i % 10) + 1), 'light') }}" alt="">
                            </div>
                            <a class="h4 mx-4 mb-0" href="/#contacto">{{ $s->nombre }}</a>
                        </div>
                        @if($detalle)
                            <div class="bg-light px-4 pb-4">
                                <p class="mb-2">{{ $s->descripcion }}</p>
                                @if($s->precio_desde !== null)
                                    <p class="mb-0">Desde <strong class="text-primary">{{ $s->moneda }} {{ number_format((float) $s->precio_desde, 2) }}</strong>@if($s->unidad) <small>{{ $s->unidad }}</small>@endif</p>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-center">Pronto publicaremos nuestros servicios.</p>
            @endforelse
        </div>
    </div>
</div>
<!-- Service End -->
