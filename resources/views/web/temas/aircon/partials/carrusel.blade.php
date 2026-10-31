@php $slides = collect($slides ?? [])->values()->filter(fn ($s) => filled($s['titulo'] ?? null))->values(); @endphp
@if($slides->isNotEmpty())
<!-- Carousel Start -->
<div class="container-fluid p-0 mb-5">
    <div id="header-carousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner">
            @foreach($slides as $i => $s)
                @php
                    $img = \App\Support\SitioWeb::urlImagen($s['imagen'] ?? null, 'aircon/img/carousel-' . (($i % 2) + 1) . '.jpg');
                    $b1 = \App\Support\SitioWeb::enlace($s['boton_url'] ?? null);
                    $b2 = \App\Support\SitioWeb::enlace($s['boton2_url'] ?? null);
                @endphp
                <div @class(['carousel-item', 'active' => $loop->first])>
                    <img class="w-100" src="{{ $img }}" alt="{{ $s['titulo'] }}">
                    <div class="carousel-caption">
                        <div class="container">
                            <div class="row justify-content-center">
                                <div class="col-lg-7 pt-5">
                                    <h1 class="display-4 text-white mb-4 animated slideInDown">{{ $s['titulo'] }}</h1>
                                    @if(filled($s['texto'] ?? null))
                                        <p class="fs-5 text-body mb-4 pb-2 mx-sm-5 animated slideInDown">{{ $s['texto'] }}</p>
                                    @endif
                                    @if($b1 && filled($s['boton_texto'] ?? null))
                                        <a href="{{ $b1 }}" class="btn btn-primary py-3 px-5 animated slideInDown">{{ $s['boton_texto'] }}</a>
                                    @endif
                                    @if($b2 && filled($s['boton2_texto'] ?? null))
                                        <a href="{{ $b2 }}" class="btn btn-outline-primary py-3 px-5 ms-2 animated slideInDown">{{ $s['boton2_texto'] }}</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        @if($slides->count() > 1)
            <button class="carousel-control-prev" type="button" data-bs-target="#header-carousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Anterior</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#header-carousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Siguiente</span>
            </button>
        @endif
    </div>
</div>
<!-- Carousel End -->
@endif
