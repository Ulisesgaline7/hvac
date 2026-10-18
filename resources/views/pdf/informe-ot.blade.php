<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 28px 32px; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1f2937; }
    h1 { font-size: 17px; margin: 0 0 2px; }
    h2 { font-size: 12px; margin: 16px 0 6px; padding-bottom: 3px; border-bottom: 2px solid #0e7490; color: #0e7490; }
    table { width: 100%; border-collapse: collapse; }
    td, th { padding: 4px 6px; vertical-align: top; }
    .datos td { border-bottom: 1px solid #e5e7eb; }
    .datos td.k { width: 18%; color: #6b7280; }
    .filas td { border-bottom: 1px solid #f3f4f6; }
    .filas td.k { width: 55%; }
    .fuera { color: #b91c1c; font-weight: bold; }
    .equipo { margin-top: 12px; border: 1px solid #d1d5db; padding: 8px; page-break-inside: avoid; }
    .equipo-titulo { font-size: 11px; font-weight: bold; }
    .badge { font-size: 9px; padding: 1px 6px; border-radius: 8px; color: #fff; }
    .ok { background: #15803d; } .borrador { background: #b45309; } .sin { background: #6b7280; }
    .alerta { background: #fef2f2; border-left: 3px solid #b91c1c; padding: 3px 6px; margin: 3px 0; color: #991b1b; }
    .pie { position: fixed; bottom: -14px; left: 0; right: 0; text-align: center; color: #9ca3af; font-size: 8px; }
    .bloque { margin: 4px 0; }
</style>
</head>
<body>
    <h1>Informe de servicio · {{ $ot->numero }}</h1>
    <div style="color:#6b7280">Generado el {{ $generado }}</div>

    <h2>Datos generales</h2>
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

    <h2>Equipos intervenidos ({{ $lineas->count() }})</h2>

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
                        <div style="font-size:8px; color:#6b7280;">{{ $f['titulo'] }}</div>
                    </div>
                @endforeach
            @endif

            @if ($l['firmas'])
                <table style="margin-top:8px;"><tr>
                    @foreach ($l['firmas'] as $f)
                        <td style="width:50%; text-align:center;">
                            <img src="{{ $f['src'] }}" style="height:60px;">
                            <div style="border-top:1px solid #6b7280; font-size:8px; color:#6b7280;">{{ $f['titulo'] }}</div>
                        </td>
                    @endforeach
                </tr></table>
            @endif
        </div>
    @empty
        <p>Esta orden no tiene equipos registrados.</p>
    @endforelse

    {{-- Evidencias y firmas: se conectan en el módulo de evidencias --}}

    <div class="pie">{{ $ot->numero }} · Informe generado el {{ $generado }}</div>
</body>
</html>
