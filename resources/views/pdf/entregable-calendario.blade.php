@extends('pdf.layout-marca')

@section('documento', $titulo . ' ' . $ot->numero)
@section('titulo-documento', 'Calendario de mantenimiento')
@section('subtitulo-documento', 'Propuesta · ' . $ot->numero)
@section('pie-extra', '· ' . $ot->numero . ' · Generado el ' . $generado)

@push('estilos')
<style>
    .t td, .t th { border-bottom: 0.6pt solid #D9D9D9; padding: 5px 6px; text-align: left; }
    .k { color: #4B5A6E; font-size: 8pt; text-transform: uppercase; font-weight: bold; }
    .aviso { background: #fffbeb; border-left: 3px solid #b45309; padding: 5px 8px; margin: 8px 0; color: #78350f; }
</style>
@endpush

@section('contenido')
    <h1 class="doc">Calendario de mantenimiento · {{ $ot->numero }}</h1>
    <p><strong>Cliente:</strong> {{ $cliente?->razon_social }} @if ($sucursal) · <strong>Sucursal:</strong> {{ $sucursal->nombre }}@endif</p>

    <div class="aviso">
        <strong>Propuesta.</strong> Visitas sugeridas cada {{ $cada }} {{ $cada === 1 ? 'mes' : 'meses' }} a partir del {{ $desde }}.
        Las fechas son tentativas, se confirman con el cliente y no constituyen un contrato de mantenimiento preventivo.
    </div>

    <div class="k">Equipos</div>
    <ul>@foreach ($equipos as $e)<li>{{ $e['codigo'] }}@if ($e['tipo']) · {{ $e['tipo'] }}@endif</li>@endforeach</ul>

    <table class="t" style="width:100%; margin-top:8px;">
        <tr><th>Visita</th><th>Fecha sugerida</th><th>Actividad</th><th>Confirmada</th></tr>
        @foreach ($fechas as $f)
            <tr><td>{{ $f['n'] }}</td><td>{{ $f['fecha'] }}</td><td>Mantenimiento preventivo</td><td>&#9744;</td></tr>
        @endforeach
    </table>
@endsection
