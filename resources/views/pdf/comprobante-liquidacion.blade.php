@extends('pdf.layout-marca')

@section('documento', $liq->numero)
@section('titulo-documento', 'Comprobante de pago a técnico')
@section('subtitulo-documento', $liq->numero)

@section('contenido')
    <h1 class="doc">Comprobante de pago a técnico</h1>

    @if ($prueba)
        <div class="caja" style="border-color:#C0392B; color:#C0392B; font-weight:bold; margin-bottom:10pt;">
            Documento de prueba, sin validez fiscal.
        </div>
    @endif
    @if ($liq->estado === 'anulada')
        <div class="caja" style="border-color:#C0392B; color:#C0392B; font-weight:bold; margin-bottom:10pt;">
            ANULADO el {{ $liq->anulada_en?->format('d/m/Y') }}. {{ $liq->motivo_anulacion }}
        </div>
    @endif

    <div class="seccion">Datos del pago</div>
    <table>
        <tr>
            <td class="linea" style="width:50%"><span class="etiqueta">Número</span><br>{{ $liq->numero }}</td>
            <td class="linea"><span class="etiqueta">Fecha de pago</span><br>{{ $liq->pagada_en?->format('d/m/Y') ?? '—' }}</td>
        </tr>
        <tr>
            <td class="linea"><span class="etiqueta">Técnico</span><br>{{ $tecnico?->user?->name ?? '—' }}</td>
            <td class="linea"><span class="etiqueta">RTN</span><br>{{ $tecnico?->rtn ?: '—' }}</td>
        </tr>
        <tr>
            <td class="linea"><span class="etiqueta">Nivel · Modalidad</span><br>{{ $tecnico?->nivel ?: '—' }} · {{ $tecnico?->modalidad ?: '—' }}</td>
            <td class="linea"><span class="etiqueta">Semana</span><br>{{ $liq->semana_inicio->format('d/m/Y') }} al {{ $liq->semana_fin->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="linea"><span class="etiqueta">Método</span><br>{{ $liq->metodo_pago ? ucfirst($liq->metodo_pago) : '—' }}</td>
            <td class="linea"><span class="etiqueta">Referencia</span><br>{{ $liq->referencia_pago ?: '—' }}</td>
        </tr>
    </table>

    <div class="seccion">Detalle</div>
    <table style="width:100%; border-collapse:collapse;">
        <tr style="background:#F3F4F6;">
            <td style="padding:3pt;"><b>OT</b></td>
            <td style="padding:3pt;"><b>Cliente</b></td>
            <td style="padding:3pt;"><b>Concepto</b></td>
            <td style="padding:3pt;"><b>Rol</b></td>
            <td style="padding:3pt; text-align:right;"><b>%</b></td>
            <td style="padding:3pt; text-align:right;"><b>Monto (L)</b></td>
        </tr>
        @forelse ($pagos as $p)
            <tr>
                <td style="padding:3pt; border-bottom:0.4pt solid #E5E7EB;">{{ $p->ordenTrabajo?->numero ?? '—' }}</td>
                <td style="padding:3pt; border-bottom:0.4pt solid #E5E7EB;">{{ $p->ordenTrabajo?->cliente?->razon_social ?? '—' }}</td>
                <td style="padding:3pt; border-bottom:0.4pt solid #E5E7EB;">{{ $p->concepto }}</td>
                <td style="padding:3pt; border-bottom:0.4pt solid #E5E7EB;">{{ ucfirst((string) $p->rol) }}</td>
                <td style="padding:3pt; border-bottom:0.4pt solid #E5E7EB; text-align:right;">{{ rtrim(rtrim(number_format((float) $p->porcentaje, 2), '0'), '.') }}</td>
                <td style="padding:3pt; border-bottom:0.4pt solid #E5E7EB; text-align:right;">{{ number_format((float) $p->monto, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="6" style="padding:6pt; color:#6B7280;">Sin detalle: los pagos de un comprobante anulado quedan liberados para una nueva liquidación.</td></tr>
        @endforelse
    </table>

    <div class="seccion">Totales</div>
    <table style="width:50%; margin-left:50%;">
        <tr><td>Bruto</td><td style="text-align:right;">L {{ number_format((float) $liq->bruto, 2) }}</td></tr>
        @if ($liq->retencion_pct)
            <tr><td>Retención ({{ rtrim(rtrim(number_format((float) $liq->retencion_pct, 2), '0'), '.') }} %)</td><td style="text-align:right;">- L {{ number_format((float) $liq->retencion, 2) }}</td></tr>
        @endif
        <tr><td><b>Neto pagado</b></td><td style="text-align:right;"><b>L {{ number_format((float) $liq->neto, 2) }}</b></td></tr>
    </table>

    <table style="margin-top:46pt;">
        <tr>
            <td style="width:40%"><div class="firma">Recibe: {{ $liq->recibe_nombre ?: '' }}</div></td>
            <td></td>
            <td style="width:40%"><div class="firma">Paga</div></td>
        </tr>
    </table>
@endsection
