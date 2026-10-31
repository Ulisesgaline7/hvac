@extends(\App\Support\SitioWeb::vista('layout'))
@php
    $inicio = \App\Models\WebPagina::inicio();
    $esAircon = \App\Support\SitioWeb::tema() === 'aircon';
    $paginaInicio = $inicio ?? ($esAircon ? new \App\Models\WebPagina(['bloques' => \App\Support\PlantillasWeb::bloques('inicio_aircon')]) : null);
@endphp

@if($paginaInicio)
    @section('titulo', $inicio?->meta_titulo ?: '')
    @section('descripcion', $inicio?->meta_descripcion ?: '')
    @if($inicio?->imagen)
        @section('imagen_og', asset('storage/' . $inicio->imagen))
    @endif

    @section('contenido')
        @include('web.partials.bloques', ['pagina' => $paginaInicio])
    @endsection
@else
@section('contenido')
@php
    $aj = \App\Models\AjusteWeb::valores();
    $servicios = \App\Models\ServicioWeb::where('visible', true)->orderBy('orden')->orderBy('id')->get();
    $datos = [
        'telefono'  => $aj['telefono'] ?? null,
        'whatsapp'  => $aj['whatsapp'] ?? null,
        'correo'    => $aj['correo'] ?? null,
        'horario'   => $aj['horario'] ?? null,
        'direccion' => $aj['direccion'] ?? null,
    ];
    $ciudades = collect(explode(',', $aj['ciudades'] ?? ''))->map(fn ($c) => trim($c))->filter()->values()->all();
@endphp
    <section class="hero">
        <div class="wrap">
            <div>
                <h1>{{ $aj['hero_titulo'] ?? 'Frío y clima bajo control total.' }}</h1>
                <p>{{ $aj['hero_texto'] ?? 'Instalamos, mantenemos y reparamos aire acondicionado, refrigeración y ventilación.' }}</p>
                <div class="cta">
                    <a class="btn btn-c" href="#contacto">Pedir cotización</a>
                    <a class="btn btn-o" href="#servicios">Ver servicios</a>
                </div>
            </div>
            <img class="dial" src="{{ asset('brand/control-total-simbolo.svg') }}" alt="" width="380" height="380">
        </div>
    </section>

    <section class="sec" id="servicios">
        <div class="wrap">
            <h2>Servicios</h2>
            <p class="lead">Para hogares, negocios e industria. Un solo equipo se encarga desde la instalación hasta el mantenimiento.</p>
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

    <section class="sec" id="proceso" style="background:#fff;border-block:1px solid var(--line)">
        <div class="wrap">
            <h2>Cómo trabajamos</h2>
            <p class="lead">Un proceso claro, en el que siempre sabes en qué paso está tu trabajo.</p>
            <ol class="steps">
                <li><h3>Visita y diagnóstico</h3><p>Revisamos tu equipo o tu espacio y te explicamos qué necesitas.</p></li>
                <li><h3>Cotización clara</h3><p>Recibes la cotización por enlace y la aceptas en línea, con firma digital.</p></li>
                <li><h3>Trabajo en sitio</h3><p>Un técnico identificado realiza el servicio y registra el equipo con su código QR.</p></li>
                <li><h3>Informe y garantía</h3><p>Te entregamos un informe con código de verificación, y tu garantía queda registrada.</p></li>
            </ol>
        </div>
    </section>

    <section class="sec band" id="control">
        <div class="wrap">
            <h2>Por qué Control Total</h2>
            <p class="lead">El nombre no es casualidad: llevamos el control de cada equipo y cada servicio, y tú también puedes verlo.</p>
            <div class="feat">
                <div><h3>Cada equipo, con su historial</h3><p>Un código QR en cada equipo permite ver su ficha, sus servicios y sus mantenimientos.</p></div>
                <div><h3>Cotizaciones que se aceptan en línea</h3><p>Sin papeles ni idas y vueltas: aceptas o rechazas desde tu teléfono.</p></div>
                <div><h3>Informes verificables</h3><p>Cada informe lleva un código con el que se puede comprobar que es auténtico.</p></div>
                <div><h3>Control del refrigerante</h3><p>Llevamos la bitácora de cada cilindro y de cada carga usada en tus equipos.</p></div>
                <div><h3>Portal para clientes</h3><p>Consulta tus cotizaciones, órdenes de trabajo, facturas y recibos cuando quieras.</p></div>
                <div><h3>Técnicos siempre conectados</h3><p>Nuestros técnicos usan una app que sigue funcionando aunque no haya internet.</p></div>
            </div>
        </div>
    </section>

    @if(count($ciudades))
    <section class="sec" id="cobertura">
        <div class="wrap">
            <h2>Dónde atendemos</h2>
            <p class="lead">Estamos cerca de ti.</p>
            <div class="chips">@foreach($ciudades as $c)<span>{{ $c }}</span>@endforeach</div>
        </div>
    </section>
    @endif

    <section class="sec" id="contacto" style="background:#fff;border-top:1px solid var(--line)">
        <div class="wrap contact">
            <div>
                <h2>Pide tu cotización</h2>
                <p class="lead">Cuéntanos qué necesitas y te respondemos lo antes posible.</p>
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
@endsection
@endif
