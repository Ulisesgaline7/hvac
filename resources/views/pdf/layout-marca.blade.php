{{-- Layout base de PDF · se personaliza desde Admin > Documentos. Uso: @extends('pdf.layout-marca') --}}
@php
    $m = \App\Support\DocumentoMarca::todo();
    $logo = \App\Support\DocumentoMarca::logo();
    $agua = 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('images/pdf/marca-agua.png')));
    $linea = collect([$m['empresa_direccion'], $m['empresa_telefono'], $m['empresa_correo'], $m['empresa_rtn'] ? 'RTN ' . $m['empresa_rtn'] : null])->filter()->implode(' · ');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>@yield('documento', $m['empresa_nombre'])</title>
<style>
    @page { margin: 96pt 51pt 56pt 51pt; }
    * { box-sizing: border-box; }
    body { font-family: DejaVu Sans, Helvetica, sans-serif; font-size: 8.6pt; color: #1A1A1A; margin: 0; }

    #encabezado { position: fixed; top: -96pt; left: -51pt; width: 612pt; height: 74pt; background: {{ $m['color_oscuro'] }}; border-bottom: 3pt solid {{ $m['color_primario'] }}; }
    #encabezado img.logo { position: absolute; top: 17pt; left: 51pt; height: 40pt; }
    #encabezado .empresa { position: absolute; top: 28pt; left: 100pt; font-size: 12pt; font-weight: bold; color: #FFFFFF; }
    #encabezado .titulo { position: absolute; top: 24pt; right: 51pt; width: 230pt; text-align: right; }
    #encabezado .titulo b { display: block; font-size: 11.5pt; color: #FFFFFF; text-transform: uppercase; }
    #encabezado .titulo span { display: block; margin-top: 3pt; font-size: 8pt; color: {{ $m['color_acento'] }}; }
    #pie { position: fixed; bottom: -56pt; left: -51pt; width: 612pt; height: 34pt; background: {{ $m['color_oscuro'] }}; border-top: 2pt solid {{ $m['color_acento'] }}; }
    #pie .texto { position: absolute; top: 8pt; left: 51pt; width: 420pt; font-size: 6.8pt; color: #FFFFFF; }
    #pie .pagina { position: absolute; top: 8pt; right: 51pt; font-size: 7pt; color: #FFFFFF; }
    #pie .pagina:after { content: "Página " counter(page); }
    #marca-agua { position: fixed; bottom: 20pt; right: -10pt; width: 165pt; height: 165pt; }

    h1.doc { font-size: 16pt; color: {{ $m['color_oscuro'] }}; margin: 6pt 0 10pt; }
    .seccion { background: #E6F0FF; border-left: 3pt solid {{ $m['color_primario'] }}; color: {{ $m['color_oscuro'] }}; font-weight: bold;
               font-size: 8.4pt; text-transform: uppercase; padding: 4pt 7pt; margin: 12pt 0 7pt; }
    table { width: 100%; border-collapse: collapse; }
    table.tabla th { background: {{ $m['color_oscuro'] }}; color: #FFFFFF; font-size: 7.6pt; text-align: left; padding: 4pt 5pt; border: 0.6pt solid {{ $m['color_oscuro'] }}; }
    table.tabla td { border: 0.6pt solid #D9D9D9; padding: 4pt 5pt; }
    table.tabla tr:nth-child(even) td { background: #F4F8FF; }
    .etiqueta { font-size: 7pt; color: #4B5A6E; text-transform: uppercase; font-weight: bold; }
    .linea { border-bottom: 0.6pt solid #D9D9D9; padding: 3pt 0; }
    .caja { background: #F4F8FF; border: 0.6pt solid #D9D9D9; padding: 6pt; }
    .firma { border-top: 0.8pt solid {{ $m['color_oscuro'] }}; text-align: center; padding-top: 3pt; color: #4B5A6E; font-size: 7.6pt; }
    .azul { color: {{ $m['color_primario'] }}; } .marino { color: {{ $m['color_oscuro'] }}; } .cian { color: {{ $m['color_acento'] }}; }
</style>
@stack('estilos')
</head>
<body>
    <img id="marca-agua" src="{{ $agua }}">

    <div id="encabezado">
        <img class="logo" src="{{ $logo }}">
        <div class="empresa">{{ $m['empresa_nombre'] }}</div>
        <div class="titulo">
            <b>@yield('titulo-documento')</b>
            <span>@yield('subtitulo-documento')</span>
        </div>
    </div>

    <div id="pie">
        <div class="texto">{{ $m['pie_texto'] }}@if($linea)<br>{{ $linea }}@endif @yield('pie-extra')</div>
        <div class="pagina"></div>
    </div>

    @yield('contenido')
</body>
</html>
