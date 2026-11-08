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
    <p>Se hace constar que el personal del cliente recibió la capacitación y la documentación indicadas.</p>

    @forelse ($equipos as $e)
        <div class="g">
            <strong>Equipo {{ $e['codigo'] }}</strong>
            @if ($e['capacitado']) · Capacitación realizada @endif
            @if ($e['entregado'])
                <div class="k" style="margin-top:6px;">Entregado</div>
                <ul>@foreach ($e['entregado'] as $d)<li>{{ $d }}</li>@endforeach</ul>
                <div style="font-size:9pt; color:#4B5A6E;">
                    @if ($e['copia_digital'])Original entregado en físico · copia digital archivada
                    @else Copia pendiente de escanear @endif
                </div>
            @endif
        </div>
    @empty
        <p>No hay capacitación ni documentos registrados en las fichas de esta orden.</p>
    @endforelse

    <div style="margin-top:16px; text-align:center;">
        @if ($firma_cliente)<img src="{{ $firma_cliente }}" style="height:60px;">@endif
        <div class="k">Firma del cliente</div>
        @if ($recibe)<div>{{ $recibe }}@if ($cargo) · {{ $cargo }}@endif</div>@endif
    </div>
    @include('pdf.partials.firmas', ['doc' => 'capacitacion'])
@endsection
