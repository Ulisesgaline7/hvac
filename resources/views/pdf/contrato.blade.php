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
        @if($repFirma)
            <table style="border-collapse:collapse; margin:0 auto;"><tr>
                <td style="vertical-align:bottom; padding:0;"><img src="{{ $repFirma }}" style="height:44pt"></td>
                @if (\App\Support\FirmaEmpresa::activa('contrato') && ($selloEmpresa = \App\Support\FirmaEmpresa::sello()))
                    <td style="vertical-align:bottom; padding:0 0 0 8pt;"><img src="{{ $selloEmpresa }}" style="height:36pt"></td>
                @endif
            </tr></table>
        @else
            <div style="height:60pt"></div>
        @endif
        <div class="firma">Por la Empresa: {{ $rep['nombre'] ?? '' }}<br>{{ $rep['cargo'] ?? '' }}<br>{{ $m['empresa_nombre'] ?? '' }}</div>
    </td>
</tr>
</table>
@endsection
