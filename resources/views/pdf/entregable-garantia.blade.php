@extends('pdf.layout-marca')

@section('documento', $titulo . ' ' . $ot->numero)
@section('titulo-documento', $titulo)
@section('subtitulo-documento', $ot->numero)
@section('pie-extra', '· ' . $ot->numero . ' · Generado el ' . $generado)

@push('estilos')
<style>
    .g { margin-top: 10px; border: 0.6pt solid #D9D9D9; padding: 8px; page-break-inside: avoid; }
    .k { color: #4B5A6E; font-size: 8pt; text-transform: uppercase; font-weight: bold; }
</style>
@endpush

@section('contenido')
    <h1 class="doc">{{ $titulo }} · {{ $ot->numero }}</h1>
    <p><strong>Cliente:</strong> {{ $cliente?->razon_social }} @if ($sucursal) · <strong>Sucursal:</strong> {{ $sucursal->nombre }}@endif</p>

    @forelse ($garantias as $g)
        <div class="g">
            <strong>{{ $g['numero'] }}</strong>@if ($g['equipo']) · Equipo {{ $g['equipo'] }}@endif<br>
            Servicio: {{ ['instalacion' => 'Instalación', 'reparacion' => 'Reparación', 'mantenimiento' => 'Mantenimiento'][$g['categoria'] ?? ''] ?? ucfirst($g['categoria'] ?? '') }}<br>
            Vigencia: {{ $g['dias'] }} días (del {{ $g['desde'] }} al {{ $g['hasta'] }})<br>
            @if ($g['cubre'])<span class="k">Cubre</span><br>{{ $g['cubre'] }}<br>@endif
            @if ($g['no_cubre'])<span class="k">No cubre</span><br>{{ $g['no_cubre'] }}<br>@endif
            @if ($g['quien_corrige'])<span class="k">Quién corrige</span><br>{{ $g['quien_corrige'] }}@endif
@include("pdf.partials.qr-verificacion", ["token" => $g["token"], "que" => "esta garantía"])
        </div>
    @empty
        <p>Esta orden no tiene garantías emitidas.</p>
    @endforelse

    @if ($firma_cliente)
        <div style="margin-top:16px; text-align:center;">
            <img src="{{ $firma_cliente }}" style="height:60px;"><div class="k">Firma del cliente</div>
        </div>
    @endif
    @include('pdf.partials.firmas', ['doc' => 'garantia'])
@if (! empty($garantias))
    @include("pdf.partials.condiciones-garantia")
@endif
@endsection
