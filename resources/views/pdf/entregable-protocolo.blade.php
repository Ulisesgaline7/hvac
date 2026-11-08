@extends('pdf.layout-marca')

@section('documento', $titulo . ' ' . $ot->numero)
@section('titulo-documento', $titulo)
@section('subtitulo-documento', $ot->numero)
@section('pie-extra', '· ' . $ot->numero . ' · Generado el ' . $generado)

@push('estilos')
<style>
    .g { margin-top: 10px; border: 0.6pt solid #D9D9D9; padding: 8px; }
    .k { color: #4B5A6E; font-size: 8pt; text-transform: uppercase; font-weight: bold; margin-top: 6px; }
    table.t { width: 100%; border-collapse: collapse; margin-top: 4px; }
    table.t td { border-bottom: 0.4pt solid #E5E5E5; padding: 2px 4px; font-size: 9pt; }
    table.t td.v { text-align: right; font-weight: bold; width: 35%; }
</style>
@endpush

@section('contenido')
    <h1 class="doc">{{ $titulo }} · {{ $ot->numero }}</h1>
    <p><strong>Cliente:</strong> {{ $cliente?->razon_social }} @if ($sucursal) · <strong>Sucursal:</strong> {{ $sucursal->nombre }}@endif</p>

    @forelse ($equipos as $e)
        <div class="g">
            <strong>Equipo {{ $e['codigo'] }}</strong>
            @if (! $e['tiene_ficha'])
                <p>Este equipo no tiene ficha registrada.</p>
            @else
                @if ($e['mediciones'])
                    <div class="k">Mediciones de puesta en marcha</div>
                    <table class="t">
                        @foreach ($e['mediciones'] as $m)<tr><td>{{ $m['etiqueta'] }}</td><td class="v">{{ $m['valor'] }}</td></tr>@endforeach
                        @if ($e['salto'])<tr><td>Salto térmico (retorno − suministro)</td><td class="v">{{ $e['salto'] }}</td></tr>@endif
                    </table>
                @else
                    <p style="font-size:9pt;color:#4B5A6E;">La ficha de este equipo no incluye campos de medición.</p>
                @endif

                @if ($e['refrigerante'])
                    <div class="k">Refrigerante</div>
                    <table class="t">@foreach ($e['refrigerante'] as $m)<tr><td>{{ $m['etiqueta'] }}</td><td class="v">{{ $m['valor'] }}</td></tr>@endforeach</table>
                @endif

                @if ($e['checklist'])
                    <div class="k">Verificaciones</div>
                    <table class="t">@foreach ($e['checklist'] as $m)<tr><td>{{ $m['etiqueta'] }}</td><td class="v">{{ $m['valor'] }}</td></tr>@endforeach</table>
                @endif

                @if ($e['pruebas'])<div class="k">Pruebas finales y observaciones</div><div style="font-size:9pt;">{{ $e['pruebas'] }}</div>@endif

                <table style="width:100%; margin-top:10px; page-break-inside: avoid;"><tr>
                    <td style="width:50%; text-align:center;">
                        @if ($e['firma_tecnico'])<img src="{{ $e['firma_tecnico'] }}" style="height:50px;">@endif
                        <div class="k">Firma del técnico</div>
                    </td>
                    <td style="width:50%; text-align:center;">
                        @if ($e['firma_cliente'])<img src="{{ $e['firma_cliente'] }}" style="height:50px;">@endif
                        <div class="k">Cliente · recibe conforme</div>
                    </td>
                </tr></table>
                @if ($e['fecha_ficha'])<div style="font-size:8pt;color:#4B5A6E;text-align:center;">Ficha registrada el {{ $e['fecha_ficha'] }}</div>@endif
            @endif
        </div>
    @empty
        <p>Esta orden no tiene equipos registrados.</p>
    @endforelse

    @if ($recibe)<p style="margin-top:12px;">Recibe: {{ $recibe }}@if ($cargo) · {{ $cargo }}@endif</p>@endif
    @include('pdf.partials.firmas', ['doc' => 'protocolo'])
@endsection
