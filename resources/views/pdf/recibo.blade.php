@extends('pdf.layout-marca')

@section('documento', $pago->numero)
@section('titulo-documento', $titulo)
@section('subtitulo-documento', $pago->numero)

@section('contenido')
    <h1 class="doc">{{ $titulo }}</h1>

    @if ($prueba)
        <div class="caja" style="border-color:#C0392B; color:#C0392B; font-weight:bold; margin-bottom:10pt;">
            Documento de prueba, sin validez fiscal.
        </div>
    @endif

    <div class="seccion">Datos del pago</div>
    <table>
        <tr>
            <td class="linea" style="width:50%"><span class="etiqueta">Número</span><br>{{ $pago->numero }}</td>
            <td class="linea"><span class="etiqueta">Fecha</span><br>{{ $pago->fecha->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="linea"><span class="etiqueta">Cliente</span><br>{{ $pago->cliente?->razon_social }}</td>
            <td class="linea"><span class="etiqueta">Método</span><br>{{ $pago->metodo ? ucfirst($pago->metodo) : '—' }}</td>
        </tr>
        <tr>
            <td class="linea"><span class="etiqueta">Referencia</span><br>{{ $pago->referencia ?: '—' }}</td>
            <td class="linea">
                <span class="etiqueta">Trabajo</span><br>{{ $pago->ordenTrabajo?->numero ?: '—' }}
                @if ($pago->factura) · Factura {{ $pago->factura->numero }} @endif
            </td>
        </tr>
    </table>

    <div class="seccion">Monto recibido</div>
    <div class="caja" style="font-size:15pt; font-weight:bold; color:#082A4A;">
        L {{ number_format((float) $pago->monto, 2) }}
    </div>

    @if (! is_null($acumulado))
        <p style="margin-top:8pt;">Total recibido como anticipo para este trabajo: <b>L {{ number_format($acumulado, 2) }}</b>.</p>
    @endif

    @if ($pago->tipo === 'anticipo')
        <p style="margin-top:10pt; color:#4B5A6E;">
            Este documento acredita el depósito recibido y no es una factura. El valor recibido se aplicará
            al total del trabajo según las condiciones acordadas.
        </p>
    @endif

    <table style="margin-top:46pt;">
        <tr>
            <td style="width:40%"><div class="firma">Recibido por</div></td>
            <td></td>
            <td style="width:40%"><div class="firma">Cliente</div></td>
        </tr>
    </table>
@endsection
