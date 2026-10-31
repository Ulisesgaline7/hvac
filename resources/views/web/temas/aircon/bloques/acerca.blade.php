@php
    $destacados = collect($d['destacados'] ?? [])->values()->filter(fn ($x) => filled($x['texto'] ?? null));
    $aj = \App\Support\SitioWeb::ajustes();
    $telefono = $aj['telefono'] ?? null;
    $correo = $aj['correo'] ?? null;
    $verContacto = ($d['mostrar_contacto'] ?? true) && (filled($telefono) || filled($correo));
@endphp
<!-- About Start -->
<div {{ \App\Support\SitioWeb::seccionAircon($d) }}>
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6 wow fadeInUp" data-wow-delay="0.5s">
                <div class="h-100">
                    <h1 class="display-6 mb-5">{{ $d['titulo'] ?? '' }}</h1>
                    @if($destacados->isNotEmpty())
                    <div class="row g-4 mb-4">
                        @foreach($destacados as $x)
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center">
                                    <img class="flex-shrink-0 me-3" src="{{ \App\Support\SitioWeb::iconoAircon($x['icono'] ?? '07') }}" alt="">
                                    <h5 class="mb-0">{{ $x['texto'] }}</h5>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @endif
                    <div class="mb-4 contenido-rico">{!! $d['parrafo'] ?? '' !!}</div>
                    @if($verContacto)
                    <div class="border-top mt-4 pt-4">
                        <div class="row">
                            @if(filled($telefono))
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center">
                                    <div class="btn-lg-square bg-primary rounded-circle me-3"><i class="fa fa-phone-alt text-white"></i></div>
                                    <h5 class="mb-0">{{ $telefono }}</h5>
                                </div>
                            </div>
                            @endif
                            @if(filled($correo))
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center">
                                    <div class="btn-lg-square bg-primary rounded-circle me-3"><i class="fa fa-envelope text-white"></i></div>
                                    <h5 class="mb-0">{{ $correo }}</h5>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            <div class="col-lg-6">
                <div class="row g-3">
                    <div class="col-6 text-end">
                        <img class="img-fluid w-75 wow zoomIn" data-wow-delay="0.1s" src="{{ \App\Support\SitioWeb::urlImagen($d['imagen1'] ?? null, 'aircon/img/about-1.jpg') }}" alt="" style="margin-top: 25%;">
                    </div>
                    <div class="col-6 text-start">
                        <img class="img-fluid w-100 wow zoomIn" data-wow-delay="0.3s" src="{{ \App\Support\SitioWeb::urlImagen($d['imagen2'] ?? null, 'aircon/img/about-2.jpg') }}" alt="">
                    </div>
                    <div class="col-6 text-end">
                        <img class="img-fluid w-50 wow zoomIn" data-wow-delay="0.5s" src="{{ \App\Support\SitioWeb::urlImagen($d['imagen3'] ?? null, 'aircon/img/about-3.jpg') }}" alt="">
                    </div>
                    <div class="col-6 text-start">
                        <img class="img-fluid w-75 wow zoomIn" data-wow-delay="0.7s" src="{{ \App\Support\SitioWeb::urlImagen($d['imagen4'] ?? null, 'aircon/img/about-4.jpg') }}" alt="">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- About End -->
