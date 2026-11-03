@extends('pdf.layout-marca')

@php($ref = $d->codigo_interno ?: $d->codigo)

@section('documento', $d->nombre)
@section('titulo-documento', $d->etiquetaDiseno())
@section('subtitulo-documento', $ref)
@section('pie-extra', '· ' . $ref . ' · Versión ' . $d->version)

@push('estilos')
<style>
    .cuerpo { font-size: 9pt; line-height: 1.45; }
    .cuerpo h1, .cuerpo h2 { font-size: 11pt; color: {{ \App\Support\DocumentoMarca::c('oscuro') }}; margin: 12pt 0 4pt; }
    .cuerpo h3, .cuerpo h4 { font-size: 9.6pt; color: {{ \App\Support\DocumentoMarca::c('primario') }}; margin: 9pt 0 3pt; }
    .cuerpo p { margin: 0 0 6pt; }
    .cuerpo ul, .cuerpo ol { margin: 0 0 6pt 14pt; padding: 0; }
    .cuerpo table { width: 100%; border-collapse: collapse; margin: 6pt 0; }
    .cuerpo th { color: #6B7280; padding: 3pt 5pt; text-align: left; font-size: 7pt; text-transform: uppercase; border-bottom: 0.8pt solid {{ \App\Support\DocumentoMarca::c('oscuro') }}; }
    .cuerpo td { border-bottom: 0.4pt solid #E5E7EB; padding: 3pt 5pt; }
    .borrador { border-color: #C0392B; color: #C0392B; font-weight: bold; margin-bottom: 8pt; }
    .firmas td { width: 33%; padding: 26pt 8pt 0; vertical-align: bottom; }
</style>
@endpush

@section('contenido')
    <h1 class="doc">{{ $d->nombre }}</h1>

    @if ($d->estado !== 'vigente')
        <div class="caja borrador">Documento en borrador, no vigente.</div>
    @endif

    <table>
        <tr>
            <td class="linea" style="width:25%"><span class="etiqueta">Código</span><br>{{ $ref }}</td>
            <td class="linea" style="width:25%"><span class="etiqueta">Versión</span><br>{{ $d->version }}</td>
            <td class="linea" style="width:25%"><span class="etiqueta">Vigente desde</span><br>{{ $d->vigente_desde?->format('d/m/Y') ?? '—' }}</td>
            <td class="linea"><span class="etiqueta">Próxima revisión</span><br>{{ $d->proxima_revision?->format('d/m/Y') ?? '—' }}</td>
        </tr>
    </table>

    <div class="cuerpo" style="margin-top:10pt">
        @if (filled($contenido))
            {!! $contenido !!}
        @else
            <p style="color:#4B5A6E">Este documento aún no tiene contenido. Edítalo en Biblioteca de documentos.</p>
        @endif
    </div>

    @if (in_array($d->diseno, ['controlado', 'contrato', 'formulario'], true))
        <table class="firmas" style="margin-top:18pt; page-break-inside: avoid;">
            <tr>
                @foreach (['Elaboró' => $d->elaborado_por, 'Revisó' => $d->revisado_por, 'Aprobó' => $d->aprobado_por] as $rol => $nombre)
                    <td>
                        <div style="text-align:center; min-height:11pt">{{ $nombre }}</div>
                        <div class="firma">{{ $rol }}</div>
                    </td>
                @endforeach
            </tr>
        </table>
    @endif
@endsection
