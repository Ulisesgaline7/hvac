@extends('pdf.layout-marca')

@section('documento', 'Informe de servicio ' . $ot->numero)
@section('titulo-documento', 'Informe de servicio')
@section('subtitulo-documento', $ot->numero)
@section('pie-extra', '· ' . $ot->numero . ' · Generado el ' . $generado)

@push('estilos')
<style>
    .datos td { border-bottom: 0.6pt solid #D9D9D9; padding: 4px 6px; vertical-align: top; }
    .datos td.k { width: 18%; color: #4B5A6E; font-size: 8pt; text-transform: uppercase; font-weight: bold; }
    .filas td { border-bottom: 0.6pt solid #E6F0FF; padding: 4px 6px; vertical-align: top; }
    .filas td.k { width: 55%; color: #4B5A6E; }
    .fuera { color: #b91c1c; font-weight: bold; }
    .equipo { margin-top: 12px; border: 0.6pt solid #D9D9D9; border-top: 2.5pt solid #087BFF; padding: 8px; page-break-inside: avoid; }
    .equipo-titulo { font-size: 10pt; font-weight: bold; color: #082A4A; }
    .badge { font-size: 7pt; padding: 1px 6px; border-radius: 8px; color: #fff; }
    .ok { background: #15803d; } .borrador { background: #b45309; } .sin { background: #4B5A6E; }
    .alerta { background: #fef2f2; border-left: 3px solid #b91c1c; padding: 3px 6px; margin: 3px 0; color: #991b1b; }
    .bloque { margin: 4px 0; }
</style>
@endpush

@section('contenido')
    <h1 class="doc">Informe de servicio · {{ $ot->numero }}</h1>
    <div style="color:#4B5A6E">Generado el {{ $generado }}</div>

    <div class="seccion">Datos generales</div>
    <table class="datos">
        <tr><td class="k">Cliente</td><td>{{ $cliente?->razon_social }}</td><td class="k">Sucursal</td><td>{{ $sucursal?->nombre }}</td></tr>
        <tr><td class="k">Tipo</td><td>{{ $ot->tipo?->getLabel() ?? $ot->tipo }}</td><td class="k">Estado</td><td>{{ $ot->estado?->getLabel() ?? $ot->estado }}</td></tr>
        <tr><td class="k">Técnico</td><td>{{ $tecnico ?? '—' }}</td><td class="k">Fecha programada</td><td>{{ $ot->fecha_programada?->format('d/m/Y') ?? '—' }}</td></tr>
        <tr><td class="k">Check-in</td><td>{{ $ot->check_in_at?->format('d/m/Y H:i') ?? '—' }}</td><td class="k">Check-out</td><td>{{ $ot->check_out_at?->format('d/m/Y H:i') ?? '—' }}</td></tr>
    </table>

    @if ($ot->descripcion)<div class="bloque"><strong>Descripción:</strong> {{ $ot->descripcion }}</div>@endif
    @if ($ot->diagnostico)<div class="bloque"><strong>Diagnóstico:</strong> {{ $ot->diagnostico }}</div>@endif
    @if ($ot->causa_raiz)<div class="bloque"><strong>Causa raíz:</strong> {{ $ot->causa_raiz }}</div>@endif
    @if ($ot->reparacion)<div class="bloque"><strong>Reparación:</strong> {{ $ot->reparacion }}</div>@endif

    <div class="seccion">Equipos intervenidos ({{ $lineas->count() }})</div>

    @forelse ($lineas as $l)
        <div class="equipo">
            <div class="equipo-titulo">
                {{ $l['codigo'] }}@if ($l['tipo']) · {{ $l['tipo'] }}@endif
                &nbsp;
                @if (! $l['tiene_ficha'])<span class="badge sin">Sin ficha</span>
                @elseif ($l['completa'])<span class="badge ok">Ficha completa</span>
                @else<span class="badge borrador">Borrador</span>@endif
            </div>

            @if ($l['estado_resultante'])<div class="bloque">Estado resultante: {{ is_object($l['estado_resultante']) ? $l['estado_resultante']->getLabel() : $l['estado_resultante'] }}</div>@endif
            @if ($l['observaciones'])<div class="bloque">Observaciones: {{ $l['observaciones'] }}</div>@endif

            @foreach ($l['alertas'] as $a)<div class="alerta">⚠ {{ $a }}</div>@endforeach

            @if ($l['filas'])
                <table class="filas">
                    @foreach ($l['filas'] as $f)
                        <tr>
                            <td class="k">{{ $f['etiqueta'] }}</td>
                            <td class="{{ $f['fuera'] ? 'fuera' : '' }}">{!! $f['nota'] ? nl2br(e($f['valor'])) : e($f['valor']) !!}@if ($f['fuera']) ⚠@endif</td>
                        </tr>
                    @endforeach
                </table>
            @endif

            @if ($l['fotos'])
                <div class="bloque"><strong>Evidencias</strong></div>
                @foreach ($l['fotos'] as $f)
                    <div style="display:inline-block; width:48%; margin:2px; vertical-align:top;">
                        <img src="{{ $f['src'] }}" style="width:100%;">
                        <div style="font-size:8px; color:#4B5A6E;">{{ $f['titulo'] }}</div>
                    </div>
                @endforeach
            @endif

            @if ($l['firmas'])
                <table style="margin-top:8px;"><tr>
                    @foreach ($l['firmas'] as $f)
                        <td style="width:50%; text-align:center;">
                            <img src="{{ $f['src'] }}" style="height:60px;">
                            <div class="firma">{{ $f['titulo'] }}</div>
                        </td>
                    @endforeach
                </tr></table>
            @endif
        </div>
    @empty
        <p>Esta orden no tiene equipos registrados.</p>
    @endforelse

    @if ($acta)
        <div class="seccion">Acta de conformidad</div>
        <table class="datos">
            <tr>
                <td class="k">Fecha</td><td>{{ $acta['fecha'] ?? '—' }}</td>
                <td class="k">Recibe</td><td>{{ $acta['recibe'] ?? '—' }}@if ($acta['cargo']) · {{ $acta['cargo'] }}@endif</td>
            </tr>
            @if ($acta['calificacion'])<tr><td class="k">Calificación</td><td colspan="3">{{ $acta['calificacion'] }} / 5</td></tr>@endif
        </table>
        @if ($acta['confirmaciones'])
            <table class="filas">
                @foreach ($acta['confirmaciones'] as $f)
                    <tr><td class="k">{{ $f['etiqueta'] }}</td><td>{{ $f['valor'] }}</td></tr>
                @endforeach
            </table>
        @endif
        @if ($acta['observaciones'])<div class="bloque"><strong>Observaciones del cliente:</strong> {{ $acta['observaciones'] }}</div>@endif
    @endif

    @if ($qr)
        <table style="margin-top:14px; page-break-inside: avoid;"><tr>
            <td style="width:70px;"><img src="{{ $qr }}" style="width:64px; height:64px;"></td>
            <td style="vertical-align:middle; color:#4B5A6E; font-size:7.6pt;">
                Verifica la autenticidad de este informe escaneando el código o visitando<br>{{ $verificacion_url }}
            </td>
        </tr></table>
    @endif
@endsection
