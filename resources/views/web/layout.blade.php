@php
    $aj = \App\Support\SitioWeb::ajustes();
    $sitio = $aj['sitio_nombre'] ?? 'Control Total';
    $titulo = trim($__env->yieldContent('titulo')) ?: ($aj['seo_titulo'] ?? ($sitio . ' | Refrigeración y aire acondicionado'));
    $descripcion = trim($__env->yieldContent('descripcion')) ?: ($aj['seo_descripcion'] ?? 'Instalación, mantenimiento y reparación de aire acondicionado, refrigeración y ventilación.');
    $ogImagen = trim($__env->yieldContent('imagen_og'));
    $logoEncabezado = \App\Support\SitioWeb::imagen($aj['logo_encabezado'] ?? null, 'brand/control-total-fondo-oscuro.png');
    $logoPie = \App\Support\SitioWeb::imagen($aj['logo_pie'] ?? null, 'brand/control-total-fondo-oscuro.png');
    $favicon = \App\Support\SitioWeb::imagen($aj['favicon'] ?? null, 'brand/control-total-simbolo.svg');
    $faviconTipo = filled($aj['favicon'] ?? null) ? \App\Support\SitioWeb::tipoImagen($aj['favicon']) : 'image/svg+xml';
    $menuPrincipal = \App\Support\SitioWeb::menu('principal');
    $menuPie = \App\Support\SitioWeb::menu('pie');
    $redes = collect(['facebook' => 'Facebook', 'instagram' => 'Instagram', 'tiktok' => 'TikTok', 'youtube' => 'YouTube'])
        ->filter(fn ($nombre, $clave) => filled($aj[$clave] ?? null));
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $titulo }}</title>
    <meta name="description" content="{{ $descripcion }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:title" content="{{ $titulo }}">
    <meta property="og:description" content="{{ $descripcion }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ $sitio }}">
    @if($ogImagen)<meta property="og:image" content="{{ $ogImagen }}">@endif
    <link rel="icon" type="{{ $faviconTipo }}" href="{{ $favicon }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('template/controltotal.css') }}">
    <style>
        .nav .has-sub{position:relative;display:inline-block}
        .nav .has-sub .sub{display:none;position:absolute;top:100%;left:0;min-width:200px;background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:6px;box-shadow:0 8px 24px rgba(0,0,0,.14);z-index:50}
        .nav .has-sub:hover .sub,.nav .has-sub:focus-within .sub{display:block}
        .nav .has-sub .sub a{display:block;padding:8px 12px;color:#0f172a;white-space:nowrap}
        .nav.open .has-sub{display:block}
        .nav.open .has-sub .sub{display:block;position:static;background:transparent;border:0;box-shadow:none;padding:0 0 0 14px}
        .nav.open .has-sub .sub a{color:inherit}
        .pagina{max-width:820px;margin:0 auto}
        .pagina h1{margin-bottom:18px}
        .pagina .portada{display:block;width:100%;height:auto;border-radius:12px;margin:0 0 24px}
        .pagina-contenido{font-size:18px;line-height:1.7}
        .pagina-contenido h2,.pagina-contenido h3{margin:28px 0 10px}
        .pagina-contenido p,.pagina-contenido ul,.pagina-contenido ol,.pagina-contenido blockquote,.pagina-contenido table{margin:0 0 16px}
        .pagina-contenido ul,.pagina-contenido ol{padding-left:24px}
        .pagina-contenido img{max-width:100%;height:auto;border-radius:8px}
        .pagina-contenido a{text-decoration:underline}
        .pagina-contenido blockquote{border-left:4px solid #cbd5e1;padding-left:16px;color:#475569}
        .pagina-contenido table{border-collapse:collapse;width:100%}
        .pagina-contenido td,.pagina-contenido th{border:1px solid #e2e8f0;padding:8px 10px;text-align:left}
        .ft .redes{display:flex;gap:14px;flex-wrap:wrap;justify-content:center;margin:10px 0}
        .img-texto{display:grid;grid-template-columns:1fr 1fr;gap:40px;align-items:center}
        .img-texto.inv .img-texto-img{order:2}
        .img-texto-img img{display:block;width:100%;height:auto;border-radius:12px}
        .galeria{display:grid;gap:14px;grid-template-columns:repeat(var(--cols,3),1fr);margin-top:20px}
        .galeria figure{margin:0}
        .galeria img{display:block;width:100%;height:220px;object-fit:cover;border-radius:10px}
        .galeria figcaption{font-size:14px;color:#64748b;margin-top:6px}
        .faq{max-width:820px;margin:20px auto 0}
        .faq details{border:1px solid var(--line,#e2e8f0);border-radius:10px;padding:14px 18px;margin-bottom:10px;background:#fff}
        .faq summary{cursor:pointer;font-weight:700}
        .faq details p{margin:10px 0 0}
        .cta-banda{text-align:center}
        .cta-banda .cta{justify-content:center}
        .embed{margin-top:20px}
        .embed iframe{max-width:100%;border:0}
        @media (max-width:760px){.img-texto{grid-template-columns:1fr}.img-texto.inv .img-texto-img{order:0}.galeria{grid-template-columns:repeat(2,1fr)}}
    </style>
</head>
<body>
@if($vp = \App\Support\SitioWeb::avisoVistaPrevia())
    <div style="background:#fde68a;color:#78350f;text-align:center;padding:6px 12px;font-size:14px">Vista previa del diseño «{{ $vp['nombre'] }}» (solo la ves tú). <a href="?tema=salir" style="text-decoration:underline">Salir de la vista previa</a></div>
@endif

<header class="hd">
    <div class="wrap">
        <a href="{{ route('home') }}" aria-label="{{ $sitio }}, inicio">
            <img src="{{ $logoEncabezado }}" alt="{{ $sitio }}">
        </a>
        <button class="menu-b" id="menuB" aria-expanded="false" aria-controls="nav">Menú</button>
        <nav class="nav" id="nav" aria-label="Principal">
            @foreach($menuPrincipal as $item)
                @php $hijos = $item->getRelation('hijosVisibles'); @endphp
                @if($hijos->isNotEmpty())
                    <div class="has-sub">
                        <a href="{{ $item->href() }}" @if($item->nueva_pestana) target="_blank" rel="noopener" @endif>{{ $item->etiqueta }}</a>
                        <div class="sub">
                            @foreach($hijos as $h)
                                <a href="{{ $h->href() }}" @if($h->nueva_pestana) target="_blank" rel="noopener" @endif>{{ $h->etiqueta }}</a>
                            @endforeach
                        </div>
                    </div>
                @else
                    <a @class(['btn btn-o' => $item->boton]) href="{{ $item->href() }}" @if($item->nueva_pestana) target="_blank" rel="noopener" @endif>{{ $item->etiqueta }}</a>
                @endif
            @endforeach
        </nav>
    </div>
</header>

<main id="inicio">
    @yield('contenido')
</main>

<footer class="ft">
    <div class="wrap">
        <img src="{{ $logoPie }}" alt="{{ $sitio }}">
        <nav aria-label="Pie de página">
            @foreach($menuPie as $item)
                <a href="{{ $item->href() }}" @if($item->nueva_pestana) target="_blank" rel="noopener" @endif>{{ $item->etiqueta }}</a>
            @endforeach
        </nav>
        @if($redes->isNotEmpty())
            <nav class="redes" aria-label="Redes sociales">
                @foreach($redes as $clave => $nombre)
                    <a href="{{ $aj[$clave] }}" target="_blank" rel="noopener">{{ $nombre }}</a>
                @endforeach
            </nav>
        @endif
        <small>{{ $aj['pie_texto'] ?? ('© ' . date('Y') . ' ' . $sitio . '. Todos los derechos reservados.') }}</small>
    </div>
</footer>

<script>
    (function () {
        var b = document.getElementById('menuB'), n = document.getElementById('nav');
        b.addEventListener('click', function () {
            var o = n.classList.toggle('open');
            b.setAttribute('aria-expanded', o);
        });
        n.addEventListener('click', function (e) {
            if (e.target.tagName === 'A') { n.classList.remove('open'); b.setAttribute('aria-expanded', 'false'); }
        });
    })();
</script>
</body>
</html>
