{{-- Layout base de PDF · Control Total. Uso: @extends('pdf.layout-marca') --}}
@php
    $img = fn (string $n) => 'data:image/png;base64,' . base64_encode(file_get_contents(public_path("images/pdf/{$n}.png")));
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>@yield('documento', 'Control Total')</title>
<style>
    @page { margin: 96pt 51pt 56pt 51pt; }
    * { box-sizing: border-box; }
    body { font-family: DejaVu Sans, Helvetica, sans-serif; font-size: 8.6pt; color: #1A1A1A; margin: 0; }

    /* Encabezado y pie fijos (se repiten en todas las paginas) */
    #encabezado { position: fixed; top: -96pt; left: -51pt; width: 612pt; height: 74pt; }
    #encabezado img.fondo { width: 612pt; height: 74pt; }
    #encabezado .titulo { position: absolute; top: 24pt; right: 51pt; width: 230pt; text-align: right; }
    #encabezado .titulo b { display: block; font-size: 11.5pt; color: #FFFFFF; text-transform: uppercase; }
    #encabezado .titulo span { display: block; margin-top: 3pt; font-size: 8pt; color: #00D1E8; }
    #pie { position: fixed; bottom: -56pt; left: -51pt; width: 612pt; height: 30pt; }
    #pie img.fondo { width: 612pt; height: 30pt; }
    #pie .texto { position: absolute; top: 10pt; left: 51pt; font-size: 7pt; color: #FFFFFF; }
    #pie .pagina { position: absolute; top: 10pt; right: 51pt; font-size: 7pt; color: #FFFFFF; }
    #pie .pagina:after { content: "Página " counter(page); }
    #marca-agua { position: fixed; bottom: 20pt; right: -10pt; width: 165pt; height: 165pt; }

    /* Componentes */
    h1.doc { font-size: 16pt; color: #082A4A; margin: 6pt 0 10pt; }
    .seccion { background: #E6F0FF; border-left: 3pt solid #087BFF; color: #082A4A; font-weight: bold;
               font-size: 8.4pt; text-transform: uppercase; padding: 4pt 7pt; margin: 12pt 0 7pt; }
    table { width: 100%; border-collapse: collapse; }
    table.tabla th { background: #082A4A; color: #FFFFFF; font-size: 7.6pt; text-align: left; padding: 4pt 5pt; border: 0.6pt solid #082A4A; }
    table.tabla td { border: 0.6pt solid #D9D9D9; padding: 4pt 5pt; }
    table.tabla tr:nth-child(even) td { background: #F4F8FF; }
    .etiqueta { font-size: 7pt; color: #4B5A6E; text-transform: uppercase; font-weight: bold; }
    .linea { border-bottom: 0.6pt solid #D9D9D9; padding: 3pt 0; }
    .caja { background: #F4F8FF; border: 0.6pt solid #D9D9D9; padding: 6pt; }
    .firma { border-top: 0.8pt solid #082A4A; text-align: center; padding-top: 3pt; color: #4B5A6E; font-size: 7.6pt; }
    .azul { color: #087BFF; } .marino { color: #082A4A; } .cian { color: #00D1E8; }
</style>
@stack('estilos')
</head>
<body>
    <img id="marca-agua" src="{{ $img('marca-agua') }}">

    <div id="encabezado">
        <img class="fondo" src="{{ $img('encabezado') }}">
        <div class="titulo">
            <b>@yield('titulo-documento')</b>
            <span>@yield('subtitulo-documento')</span>
        </div>
    </div>

    <div id="pie">
        <img class="fondo" src="{{ $img('pie') }}">
        <div class="texto">Control Total · Refrigeración y Aire Acondicionado @yield('pie-extra')</div>
        <div class="pagina"></div>
    </div>

    @yield('contenido')
</body>
</html>
