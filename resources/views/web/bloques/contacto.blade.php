@php
    $aj = \App\Support\SitioWeb::ajustes();
    $datos = [
        'telefono'  => $aj['telefono'] ?? null,
        'whatsapp'  => $aj['whatsapp'] ?? null,
        'correo'    => $aj['correo'] ?? null,
        'horario'   => $aj['horario'] ?? null,
        'direccion' => $aj['direccion'] ?? null,
    ];
@endphp
<section {{ \App\Support\SitioWeb::seccion($d) }}>
    <div class="wrap contact">
        <div>
            @if(filled($d['titulo'] ?? null))<h2>{{ $d['titulo'] }}</h2>@endif
            @if(filled($d['intro'] ?? null))<p class="lead">{{ $d['intro'] }}</p>@endif
            @include('web.partials.contacto-form')
        </div>
        <div class="info">
            <h2 style="font-size:24px">Contáctanos</h2>
            <p class="lead" style="margin-bottom:20px">También puedes escribirnos o llamarnos directamente.</p>
            @if($datos['telefono'])<p><strong>Teléfono</strong><a href="tel:{{ preg_replace('/[^\d+]/', '', $datos['telefono']) }}">{{ $datos['telefono'] }}</a></p>@endif
            @if($datos['whatsapp'])<p><strong>WhatsApp</strong><a href="https://wa.me/{{ $datos['whatsapp'] }}" target="_blank" rel="noopener">Escríbenos por WhatsApp</a></p>@endif
            @if($datos['correo'])<p><strong>Correo</strong><a href="mailto:{{ $datos['correo'] }}">{{ $datos['correo'] }}</a></p>@endif
            @if($datos['horario'])<p><strong>Horario</strong>{{ $datos['horario'] }}</p>@endif
            @if($datos['direccion'])<p><strong>Dirección</strong>{{ $datos['direccion'] }}</p>@endif
            <p><strong>¿Ya eres cliente?</strong><a href="{{ route('equipo.ingreso') }}">Ingresa a tu portal</a></p>
        </div>
    </div>
</section>
