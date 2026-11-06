@extends('pdf.layout-marca')
@section('documento', 'Contrato de servicio')
@section('titulo-documento', 'Contrato de servicio')
@section('subtitulo-documento', 'N.º ' . str_pad((string) $c->id, 5, '0', STR_PAD_LEFT))
@section('contenido')
<h1 class="doc">Contrato de servicio</h1>
@include('contratos._texto', ['c' => $c])

<div class="seccion">Firmas</div>
<table>
<tr>
    <td style="width:50%; padding-right:14pt; vertical-align:bottom">
        @if($firma)
            <img src="{{ $firma }}" style="height:60pt"><br>
        @else
            <div style="height:60pt"></div>
        @endif
        <div class="firma">
            Por el Cliente: {{ $firmante['nombre'] ?? '' }}<br>
            {{ $firmante['cargo'] ?? '' }}
            @if($firmante)<br>{{ $firmante['fecha'] }} · IP {{ $firmante['ip'] }}@endif
        </div>
    </td>
    <td style="width:50%; padding-left:14pt; vertical-align:bottom">
        <div style="height:60pt"></div>
        <div class="firma">Por la Empresa<br>{{ $m['empresa_nombre'] ?? '' }}</div>
    </td>
</tr>
</table>
@endsection
