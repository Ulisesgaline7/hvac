{{-- Layout base de PDF · minimalista. Se personaliza desde Admin > Documentos. Uso: @extends('pdf.layout-marca') --}}
@php
    $m = \App\Support\DocumentoMarca::todo();
    $logo = \App\Support\DocumentoMarca::logo();
    $linea = collect([$m['empresa_direccion'], $m['empresa_telefono'], $m['empresa_correo'], $m['empresa_rtn'] ? 'RTN ' . $m['empresa_rtn'] : null])->filter()->implode(' · ');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>@yield('documento', $m['empresa_nombre'])</title>
<style>
    @page { margin: 88pt 51pt 60pt 51pt; }
    * { box-sizing: border-box; }
    body { font-family: DejaVu Sans, Helvetica, sans-serif; font-size: 8.6pt; color: #1F2937; line-height: 1.4; margin: 0; }

    /* Encabezado y pie fijos, sobre fondo blanco */
    #encabezado { position: fixed; top: -66pt; left: 0; right: 0; height: 40pt; border-bottom: 0.6pt solid #E5E7EB; }
    #encabezado table { width: 100%; }
    #encabezado td { vertical-align: middle; padding: 0 0 8pt; }
    #encabezado img.logo { height: 28pt; }
    #encabezado .tipo { text-align: right; }
    #encabezado .tipo b { display: block; font-size: 8pt; color: {{ $m['color_primario'] }}; text-transform: uppercase; letter-spacing: 0.8pt; }
    #encabezado .tipo span { display: block; margin-top: 2pt; font-size: 7.5pt; color: #6B7280; }
    #pie { position: fixed; bottom: -44pt; left: 0; right: 0; height: 24pt; border-top: 0.6pt solid #E5E7EB; }
    #pie .texto { position: absolute; top: 6pt; left: 0; width: 400pt; font-size: 6.6pt; color: #9CA3AF; }
    #pie .pagina { position: absolute; top: 6pt; right: 0; font-size: 6.6pt; color: #9CA3AF; }
    #pie .pagina:after { content: "Página " counter(page); }

    /* Componentes */
    h1.doc { font-size: 15pt; color: {{ $m['color_oscuro'] }}; margin: 4pt 0 10pt; }
    .seccion { color: {{ $m['color_primario'] }}; font-weight: bold; font-size: 7.2pt; text-transform: uppercase;
               letter-spacing: 0.7pt; border-bottom: 0.6pt solid #E5E7EB; padding: 0 0 3pt; margin: 14pt 0 6pt; }
    table { width: 100%; border-collapse: collapse; }
    table.tabla th { color: #6B7280; font-size: 6.8pt; text-transform: uppercase; letter-spacing: 0.4pt; text-align: left;
                     padding: 4pt 5pt; border-bottom: 0.8pt solid {{ $m['color_oscuro'] }}; }
    table.tabla td { border-bottom: 0.4pt solid #E5E7EB; padding: 4pt 5pt; }
    .etiqueta { font-size: 6.6pt; color: #9CA3AF; text-transform: uppercase; letter-spacing: 0.3pt; font-weight: bold; }
    .linea { border-bottom: 0.4pt solid #E5E7EB; padding: 3pt 0; }
    .caja { border: 0.6pt solid #E5E7EB; padding: 7pt; border-radius: 3pt; }
    .firma { border-top: 0.6pt solid #9CA3AF; text-align: center; padding-top: 3pt; color: #6B7280; font-size: 7.2pt; }
    .azul { color: {{ $m['color_primario'] }}; } .marino { color: {{ $m['color_oscuro'] }}; } .cian { color: {{ $m['color_acento'] }}; }
</style>
@stack('estilos')
</head>
<body>
    <div id="encabezado">
        <table><tr>
            <td><img class="logo" src="{{ $logo }}"></td>
            <td class="tipo">
                <b>@yield('titulo-documento')</b>
                <span>@yield('subtitulo-documento')</span>
            </td>
        </tr></table>
    </div>

    <div id="pie">
        <div class="texto">{{ $m['pie_texto'] }}@if($linea)<br>{{ $linea }}@endif @yield('pie-extra')</div>
        <div class="pagina"></div>
    </div>

    @yield('contenido')
</body>
</html>
