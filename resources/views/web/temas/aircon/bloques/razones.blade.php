@php $items = collect($d['items'] ?? [])->values()->filter(fn ($x) => filled($x['titulo'] ?? null)); @endphp
<!-- Features Start -->
<div {{ \App\Support\SitioWeb::seccionAircon($d) }}>
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-6 wow fadeInUp" data-wow-delay="0.1s">
                <h1 class="display-6 mb-5">{{ $d['titulo'] ?? '' }}</h1>
                @if(filled($d['parrafo'] ?? null))<p class="mb-5">{{ $d['parrafo'] }}</p>@endif
                @foreach($items as $x)
                    <div @class(['d-flex', 'mb-5' => ! $loop->last, 'mb-0' => $loop->last])>
                        <div class="flex-shrink-0 btn-square bg-primary rounded-circle" style="width: 90px; height: 90px;">
                            <img class="img-fluid" src="{{ \App\Support\SitioWeb::iconoAircon($x['icono'] ?? '08', 'light') }}" alt="">
                        </div>
                        <div class="ms-4">
                            <h5 class="mb-3">{{ $x['titulo'] }}</h5>
                            <span>{{ $x['texto'] ?? '' }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="col-lg-6 wow fadeInUp" data-wow-delay="0.5s">
                <div class="position-relative rounded overflow-hidden h-100" style="min-height: 400px;">
                    <img class="position-absolute w-100 h-100" src="{{ \App\Support\SitioWeb::urlImagen($d['imagen'] ?? null, 'aircon/img/feature.jpg') }}" alt="" style="object-fit: cover;">
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Features End -->
