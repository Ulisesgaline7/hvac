@php
    $aj = \App\Support\SitioWeb::ajustes();
    $sitio = $aj['sitio_nombre'] ?? 'Control Total';
    $titulo = trim($__env->yieldContent('titulo')) ?: ($aj['seo_titulo'] ?? ($sitio . ' | Refrigeración y aire acondicionado'));
    $descripcion = trim($__env->yieldContent('descripcion')) ?: ($aj['seo_descripcion'] ?? 'Instalación, mantenimiento y reparación de aire acondicionado, refrigeración y ventilación.');
    $ogImagen = trim($__env->yieldContent('imagen_og'));
    $logo = filled($aj['logo_encabezado'] ?? null) ? asset('storage/' . ltrim($aj['logo_encabezado'], '/')) : null;
    $logoPie = filled($aj['logo_pie'] ?? null) ? asset('storage/' . ltrim($aj['logo_pie'], '/')) : null;
    $favicon = \App\Support\SitioWeb::imagen($aj['favicon'] ?? null, 'aircon/img/icon/icon-02-primary.png');
    $faviconTipo = filled($aj['favicon'] ?? null) ? \App\Support\SitioWeb::tipoImagen($aj['favicon']) : 'image/png';
    $menuPrincipal = \App\Support\SitioWeb::menu('principal');
    $menuBarra = \App\Support\SitioWeb::menu('barra');
    $menuPie = \App\Support\SitioWeb::menu('pie');
    $enlacesMenu = $menuPrincipal->reject(fn ($i) => $i->boton);
    $botonesMenu = $menuPrincipal->filter(fn ($i) => $i->boton);
    $redes = \App\Support\SitioWeb::redes();
    $colores = \App\Support\SitioWeb::coloresCss();
    $topbar = ($aj['topbar_mostrar'] ?? '1') !== '0';
    $vp = \App\Support\SitioWeb::avisoVistaPrevia();
    $telefono = $aj['telefono'] ?? null;
    $correo = $aj['correo'] ?? null;
    $direccion = $aj['direccion'] ?? null;
    $horario = $aj['horario'] ?? null;
    $whatsapp = $aj['whatsapp'] ?? null;
    try {
        $serviciosPie = \App\Models\ServicioWeb::where('visible', true)->orderBy('orden')->orderBy('id')->limit(6)->pluck('nombre');
    } catch (\Throwable $e) {
        $serviciosPie = collect();
    }
@endphp
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>{{ $titulo }}</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta name="description" content="{{ $descripcion }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:title" content="{{ $titulo }}">
    <meta property="og:description" content="{{ $descripcion }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ $sitio }}">
    @if($ogImagen)<meta property="og:image" content="{{ $ogImagen }}">@endif

    <!-- Favicon -->
    <link href="{{ $favicon }}" rel="icon" type="{{ $faviconTipo }}">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Slab:wght@400;600;800&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="{{ asset('aircon/lib/animate/animate.min.css') }}" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="{{ asset('aircon/css/bootstrap.min.css') }}" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="{{ asset('aircon/css/style.css') }}" rel="stylesheet">

    @if($colores !== '')
    <style>
        :root { {{ $colores }} }
        a { color: var(--primary); }
        .btn-primary { background-color: var(--primary); border-color: var(--primary); }
        .btn-secondary { background-color: var(--secondary); border-color: var(--secondary); }
        .btn-outline-primary { color: var(--primary); border-color: var(--primary); }
        .btn-outline-primary:hover { background-color: var(--primary); border-color: var(--primary); }
        .text-primary { color: var(--primary) !important; }
        .bg-primary { background-color: var(--primary) !important; }
        .border-primary { border-color: var(--primary) !important; }
        .text-secondary { color: var(--secondary) !important; }
        .bg-secondary { background-color: var(--secondary) !important; }
        .bg-dark { background-color: var(--dark) !important; }
        .bg-light { background-color: var(--light) !important; }
        .dropdown-item.active, .dropdown-item:active { background-color: var(--primary); }
    </style>
    @endif
    <style>
        .contenido-rico img { max-width: 100%; height: auto; border-radius: 6px; }
        .contenido-rico table { width: 100%; border-collapse: collapse; margin-bottom: 1rem; }
        .contenido-rico td, .contenido-rico th { border: 1px solid #dee2e6; padding: .5rem; }
        .contenido-rico blockquote { border-left: 4px solid var(--primary); padding-left: 1rem; color: #696E77; }
        .contenido-embed iframe { max-width: 100%; border: 0; }
        .navbar .navbar-brand h1 img { max-height: 60px; }
    </style>
</head>

<body>
    <!-- Spinner Start -->
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-grow text-primary" role="status"></div>
    </div>
    <!-- Spinner End -->


    @if($topbar)
    <!-- Topbar Start -->
    <div class="container-fluid bg-dark text-white-50 py-2 px-0 d-none d-lg-block">
        <div class="row gx-0 align-items-center">
            <div class="col-lg-7 px-5 text-start">
                @if(filled($telefono))
                <div class="h-100 d-inline-flex align-items-center me-4">
                    <small class="fa fa-phone-alt me-2"></small>
                    <small>{{ $telefono }}</small>
                </div>
                @endif
                @if(filled($correo))
                <div class="h-100 d-inline-flex align-items-center me-4">
                    <small class="far fa-envelope-open me-2"></small>
                    <small>{{ $correo }}</small>
                </div>
                @endif
            </div>
            <div class="col-lg-5 px-5 text-end">
                @if($menuBarra->isNotEmpty())
                <ol class="breadcrumb justify-content-end mb-0">
                    @foreach($menuBarra as $item)
                        <li class="breadcrumb-item"><a class="text-white-50 small" href="{{ $item->href() }}" @if($item->nueva_pestana) target="_blank" rel="noopener" @endif>{{ $item->etiqueta }}</a></li>
                    @endforeach
                </ol>
                @endif
            </div>
        </div>
    </div>
    <!-- Topbar End -->
    @endif


    <!-- Navbar Start -->
    <nav class="navbar navbar-expand-lg bg-white navbar-light sticky-top px-4 px-lg-5">
        <a href="{{ route('home') }}" class="navbar-brand d-flex align-items-center">
            <h1 class="m-0">
                @if($logo)
                    <img class="img-fluid" src="{{ $logo }}" alt="{{ $sitio }}">
                @else
                    <img class="img-fluid me-3" src="{{ asset('aircon/img/icon/icon-02-primary.png') }}" alt="">{{ $sitio }}
                @endif
            </h1>
        </a>
        <button type="button" class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarCollapse">
            <div class="navbar-nav mx-auto bg-light pe-4 py-3 py-lg-0">
                @foreach($enlacesMenu as $item)
                    @php $hijos = $item->getRelation('hijosVisibles'); @endphp
                    @if($hijos->isNotEmpty())
                        <div class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">{{ $item->etiqueta }}</a>
                            <div class="dropdown-menu bg-light border-0 m-0">
                                <a href="{{ $item->href() }}" class="dropdown-item" @if($item->nueva_pestana) target="_blank" rel="noopener" @endif>{{ $item->etiqueta }}</a>
                                @foreach($hijos as $h)
                                    <a href="{{ $h->href() }}" class="dropdown-item" @if($h->nueva_pestana) target="_blank" rel="noopener" @endif>{{ $h->etiqueta }}</a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <a href="{{ $item->href() }}" @class(['nav-item nav-link', 'active' => \App\Support\SitioWeb::esActivo($item->href())]) @if($item->nueva_pestana) target="_blank" rel="noopener" @endif>{{ $item->etiqueta }}</a>
                    @endif
                @endforeach
            </div>
            @if(count($redes))
            <div class="h-100 d-lg-inline-flex align-items-center d-none">
                @foreach($redes as $r)
                    <a class="btn btn-square rounded-circle bg-light text-primary {{ $loop->last ? 'me-0' : 'me-2' }}" href="{{ $r['url'] }}" target="_blank" rel="noopener" aria-label="{{ $r['nombre'] }}"><i class="{{ $r['icono'] }}"></i></a>
                @endforeach
            </div>
            @endif
            @foreach($botonesMenu as $b)
                <a class="btn btn-primary py-2 px-4 ms-lg-3 mt-3 mt-lg-0" href="{{ $b->href() }}" @if($b->nueva_pestana) target="_blank" rel="noopener" @endif>{{ $b->etiqueta }}</a>
            @endforeach
        </div>
    </nav>
    <!-- Navbar End -->

    @if(session('ok'))
        <div class="alert alert-success text-center rounded-0 mb-0" role="status">{{ session('ok') }}</div>
    @endif

    @yield('contenido')


    <!-- Footer Start -->
    <div class="container-fluid bg-dark footer mt-5 pt-5 wow fadeIn" data-wow-delay="0.1s">
        <div class="container py-5">
            <div class="row g-5">
                <div class="col-md-6">
                    <h1 class="text-white mb-4">
                        @if($logoPie)
                            <img class="img-fluid" style="max-height: 60px;" src="{{ $logoPie }}" alt="{{ $sitio }}">
                        @else
                            <img class="img-fluid me-3" src="{{ asset('aircon/img/icon/icon-02-light.png') }}" alt="">{{ $sitio }}
                        @endif
                    </h1>
                    <span>{{ $aj['pie_descripcion'] ?? '' }}</span>
                </div>
                @if(filled($horario) || filled($whatsapp))
                <div class="col-md-6">
                    @if(filled($horario))
                        <h5 class="text-light mb-4">Horario de atención</h5>
                        <p>{{ $horario }}</p>
                    @endif
                    @if(filled($whatsapp))
                        <a class="btn btn-primary py-2 px-4" href="https://wa.me/{{ preg_replace('/\D/', '', $whatsapp) }}" target="_blank" rel="noopener"><i class="fab fa-whatsapp me-2"></i>Escríbenos por WhatsApp</a>
                    @endif
                </div>
                @endif
                <div class="col-lg-3 col-md-6">
                    <h5 class="text-light mb-4">Contáctanos</h5>
                    @if(filled($direccion))<p><i class="fa fa-map-marker-alt me-3"></i>{{ $direccion }}</p>@endif
                    @if(filled($telefono))<p><i class="fa fa-phone-alt me-3"></i>{{ $telefono }}</p>@endif
                    @if(filled($correo))<p><i class="fa fa-envelope me-3"></i>{{ $correo }}</p>@endif
                </div>
                <div class="col-lg-3 col-md-6">
                    <h5 class="text-light mb-4">Nuestros servicios</h5>
                    @foreach($serviciosPie as $nombre)
                        <a class="btn btn-link" href="/#servicios">{{ $nombre }}</a>
                    @endforeach
                </div>
                <div class="col-lg-3 col-md-6">
                    <h5 class="text-light mb-4">Enlaces</h5>
                    @foreach($menuPie as $item)
                        <a class="btn btn-link" href="{{ $item->href() }}" @if($item->nueva_pestana) target="_blank" rel="noopener" @endif>{{ $item->etiqueta }}</a>
                    @endforeach
                </div>
                <div class="col-lg-3 col-md-6">
                    @if(count($redes))
                        <h5 class="text-light mb-4">Síguenos</h5>
                        <div class="d-flex">
                            @foreach($redes as $r)
                                <a class="btn btn-square rounded-circle me-1" href="{{ $r['url'] }}" target="_blank" rel="noopener" aria-label="{{ $r['nombre'] }}"><i class="{{ $r['icono'] }}"></i></a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="container-fluid copyright">
            <div class="container">
                <div class="row">
                    <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                        @if(filled($aj['pie_texto'] ?? null))
                            {{ $aj['pie_texto'] }}
                        @else
                            &copy; <a href="{{ route('home') }}">{{ $sitio }}</a>, Todos los derechos reservados.
                        @endif
                    </div>
                    <div class="col-md-6 text-center text-md-end">
                        <!--/*** The author’s attribution link below must remain intact on your website. ***/-->
                        <!--/*** If you wish to remove this credit link, please purchase the Pro Version from https://htmlcodex.com/downloading/?item=2574 ***/-->
                        Designed By <a href="https://htmlcodex.com">HTML Codex</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Footer End -->


    <!-- Back to Top -->
    <a href="#" class="btn btn-lg btn-primary btn-lg-square rounded-circle back-to-top"><i class="bi bi-arrow-up"></i></a>


    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('aircon/lib/wow/wow.min.js') }}"></script>
    <script src="{{ asset('aircon/lib/easing/easing.min.js') }}"></script>
    <script src="{{ asset('aircon/lib/waypoints/waypoints.min.js') }}"></script>

    <!-- Template Javascript -->
    <script src="{{ asset('aircon/js/main.js') }}"></script>
</body>

</html>
