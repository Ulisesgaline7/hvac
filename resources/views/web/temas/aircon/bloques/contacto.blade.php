@php
    $aj = \App\Support\SitioWeb::ajustes();
    $mapa = \App\Support\SitioWeb::mapaSrc($d['mapa'] ?? null);
    $telefono = $aj['telefono'] ?? null;
    $correo = $aj['correo'] ?? null;
    $whatsapp = $aj['whatsapp'] ?? null;
    $horario = $aj['horario'] ?? null;
    $direccion = $aj['direccion'] ?? null;
@endphp
<!-- Contact Start -->
<div {{ \App\Support\SitioWeb::seccionAircon($d) }}>
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-6 wow fadeIn" data-wow-delay="0.1s">
                @if(filled($d['titulo'] ?? null))<h1 class="display-6 mb-4">{{ $d['titulo'] }}</h1>@endif
                @if(filled($d['intro'] ?? null))<p class="mb-4">{{ $d['intro'] }}</p>@endif
                @include('web.temas.aircon.partials.contacto-form')
            </div>
            <div class="col-lg-6 wow fadeIn" data-wow-delay="0.5s" style="min-height: 450px;">
                @if($mapa)
                    <div class="position-relative overflow-hidden h-100">
                        <iframe class="position-relative w-100 h-100" src="{{ $mapa }}" style="min-height: 450px; border: 0;" allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade" tabindex="0"></iframe>
                    </div>
                @else
                    <div class="bg-light h-100 p-5">
                        <h4 class="mb-4">Contáctanos</h4>
                        @if(filled($telefono))<p class="mb-3"><i class="fa fa-phone-alt text-primary me-3"></i><a href="tel:{{ preg_replace('/[^\d+]/', '', $telefono) }}">{{ $telefono }}</a></p>@endif
                        @if(filled($whatsapp))<p class="mb-3"><i class="fab fa-whatsapp text-primary me-3"></i><a href="https://wa.me/{{ preg_replace('/\D/', '', $whatsapp) }}" target="_blank" rel="noopener">Escríbenos por WhatsApp</a></p>@endif
                        @if(filled($correo))<p class="mb-3"><i class="fa fa-envelope text-primary me-3"></i><a href="mailto:{{ $correo }}">{{ $correo }}</a></p>@endif
                        @if(filled($direccion))<p class="mb-3"><i class="fa fa-map-marker-alt text-primary me-3"></i>{{ $direccion }}</p>@endif
                        @if(filled($horario))<p class="mb-3"><i class="far fa-clock text-primary me-3"></i>{{ $horario }}</p>@endif
                        <p class="mb-0 pt-3 border-top">¿Ya eres cliente? <a href="{{ route('equipo.ingreso') }}">Ingresa a tu portal</a></p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
<!-- Contact End -->
