@extends('pdf.layout-marca')

@section('documento', $f->numero)
@section('titulo-documento', 'Factura')
@section('subtitulo-documento', $f->numero)

@section('contenido')
    <h1 class="doc">Factura {{ $f->numero }}</h1>

    @if ($prueba)
        <div class="caja" style="border-color:#C0392B; color:#C0392B; font-weight:bold; margin-bottom:10pt;">
            Documento de prueba, sin validez fiscal.
        </div>
    @endif

    <div class="seccion">Datos</div>
    <table>
        <tr>
            <td class="linea" style="width:50%"><span class="etiqueta">Cliente</span><br>{{ $f->cliente?->razon_social }}</td>
            <td class="linea"><span class="etiqueta">RTN</span><br>{{ $rtn ?: '—' }}</td>
        </tr>
        <tr>
            <td class="linea"><span class="etiqueta">Fecha de emisión</span><br>{{ $f->fecha->format('d/m/Y') }}</td>
            <td class="linea"><span class="etiqueta">Vence</span><br>{{ $f->vence->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="linea" colspan="2">
                <span class="etiqueta">Concepto</span><br>
                @if ($f->ordenTrabajo) Orden de trabajo {{ $f->ordenTrabajo->numero }}. @endif
                {{ $f->notas }}
            </td>
        </tr>
    </table>

    @if (filled($f->cai))
        <div class="seccion">Datos fiscales</div>
        <table>
            <tr>
                <td class="linea" colspan="2"><span class="etiqueta">CAI</span><br>{{ $f->cai }}</td>
            </tr>
            <tr>
                <td class="linea" style="width:50%"><span class="etiqueta">Rango autorizado</span><br>{{ $f->rango_inicial }} a {{ $f->rango_final }}</td>
                <td class="linea"><span class="etiqueta">Fecha límite de emisión</span><br>{{ $f->fecha_limite_emision?->format('d/m/Y') }}</td>
            </tr>
        </table>
    @endif

    <div class="seccion">Montos</div>
    <table class="tabla">
        <tr><th>Concepto</th><th style="text-align:right">Importe (L)</th></tr>
        @php($cotOrigen = $f->cotizacion_id ? \App\Models\Cotizacion::find($f->cotizacion_id) : null)
        @if ($cotOrigen && abs((float) $cotOrigen->subtotal - (float) $f->subtotal) < 0.01 && abs((float) $cotOrigen->isv - (float) $f->isv) < 0.01)
            @php($recNom = collect($cotOrigen->recargos ?? [])->map(fn ($v, $k) => config('tarifario.recargos.' . (is_int($k) ? $v : $k) . '.nombre'))->filter()->values())
            @php($descNom = $cotOrigen->descuento_codigo ? config('tarifario.descuentos.' . $cotOrigen->descuento_codigo . '.nombre') : null)
            @php($raF = \App\Services\ResumenAnual::resumen($cotOrigen))
            @if ($raF)
                <tr><td colspan="2"><i>{{ $cotOrigen->descuento_codigo ? config('tarifario.descuentos.' . $cotOrigen->descuento_codigo . '.nombre') : 'Plan' }} · {{ $raF['texto'] }} (esta factura corresponde a una visita)</i></td></tr>
            @endif
            <tr><td>Servicios</td><td style="text-align:right">{{ number_format((float) $cotOrigen->subtotal_servicios, 2) }}</td></tr>
            @if ((float) $cotOrigen->ajuste_minimo > 0)
                <tr><td>Ajuste a servicio mínimo por visita</td><td style="text-align:right">{{ number_format((float) $cotOrigen->ajuste_minimo, 2) }}</td></tr>
            @endif
            @if ((float) $cotOrigen->recargos_monto > 0)
                <tr><td>Recargos ({{ $recNom->implode(', ') }})</td><td style="text-align:right">{{ number_format((float) $cotOrigen->recargos_monto, 2) }}</td></tr>
            @endif
            @if ((float) $cotOrigen->descuento_monto > 0)
                <tr><td>Descuento ({{ $descNom }})</td><td style="text-align:right">− {{ number_format((float) $cotOrigen->descuento_monto, 2) }}</td></tr>
            @endif
            @if ((float) $cotOrigen->repuestos_monto > 0)
                <tr><td>Repuestos y materiales</td><td style="text-align:right">{{ number_format((float) $cotOrigen->repuestos_monto, 2) }}</td></tr>
            @endif
            @if ((float) $cotOrigen->logistica_monto > 0)
                <tr><td>Logística</td><td style="text-align:right">{{ number_format((float) $cotOrigen->logistica_monto, 2) }}</td></tr>
            @endif
            @if ((float) $cotOrigen->viaticos_monto > 0)
                <tr><td>Viáticos</td><td style="text-align:right">{{ number_format((float) $cotOrigen->viaticos_monto, 2) }}</td></tr>
            @endif
        @endif
        <tr><td>Subtotal</td><td style="text-align:right">{{ number_format((float) $f->subtotal, 2) }}</td></tr>
        <tr><td>ISV {{ (int) round(config('tarifario.isv') * 100) }} %</td><td style="text-align:right">{{ number_format((float) $f->isv, 2) }}</td></tr>
        <tr><td><b>Total</b></td><td style="text-align:right"><b>{{ number_format((float) $f->total, 2) }}</b></td></tr>
        <tr><td>Pagado</td><td style="text-align:right">{{ number_format((float) $f->pagado, 2) }}</td></tr>
        <tr><td><b>Saldo</b></td><td style="text-align:right"><b>{{ number_format($f->saldo, 2) }}</b></td></tr>
    </table>

    @if (filled($f->notas))
        <p style="margin-top:6pt"><i>{{ $f->notas }}</i></p>
    @endif

    @if ($cuotas->isNotEmpty())
        <div class="seccion">Plan de cuotas</div>
        <table class="tabla">
            <tr><th>Cuota</th><th>Vence</th><th style="text-align:right">Monto (L)</th><th style="text-align:right">Saldo (L)</th><th>Estado</th></tr>
            @foreach ($cuotas as $c)
                <tr>
                    <td>{{ $c['cuota']->numero }}</td>
                    <td>{{ $c['cuota']->vence->format('d/m/Y') }}</td>
                    <td style="text-align:right">{{ number_format((float) $c['cuota']->monto, 2) }}</td>
                    <td style="text-align:right">{{ number_format($c['saldo'], 2) }}</td>
                    <td>{{ ucfirst($c['estado']) }}</td>
                </tr>
            @endforeach
        </table>
    @endif
@include("pdf.partials.qr-verificacion", ["token" => app(\App\Services\VerificacionDocumento::class)->token($f), "que" => "esta factura"])
@endsection
