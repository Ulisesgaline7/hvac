<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 18mm 14mm; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 8.5pt; color: #222; }
    h1 { font-size: 15pt; margin: 0 0 2mm 0; }
    .muted { color: #666; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #eef1f5; text-align: left; padding: 4px; border: 1px solid #ccc; }
    td { padding: 4px; border: 1px solid #ddd; vertical-align: top; }
    .r { text-align: right; }
    .sin-borde td { border: none; padding: 1px 0; }
    .caja { border: 1px solid #ccc; padding: 6px; margin: 0 0 8px 0; }
    .total td { font-weight: bold; font-size: 10pt; background: #eef1f5; }
    .firma { width: 45%; border-top: 1px solid #333; padding-top: 3px; text-align: center; }
    ol { margin: 2mm 0 0 4mm; padding: 0; }
</style>
</head>
<body>
@php($recargos = collect($c->recargos ?? [])->map(fn ($v, $k) => config('tarifario.recargos.' . (is_int($k) ? $v : $k) . '.nombre'))->filter()->values())
@php($descuento = $c->descuento_codigo ? config('tarifario.descuentos.' . $c->descuento_codigo . '.nombre') : null)

<table class="sin-borde">
    <tr>
        <td>
            <h1>{{ $empresa['nombre'] }}</h1>
            <div class="muted">{{ $empresa['direccion'] }}<br>Tel: {{ $empresa['telefono'] }} · {{ $empresa['correo'] }}<br>RTN: {{ $empresa['rtn'] }}</div>
        </td>
        <td class="r">
            <h1>COTIZACIÓN</h1>
            <strong>{{ $c->numero }}</strong><br>
            Fecha: {{ $c->fecha?->format('d/m/Y') }}<br>
            Válida hasta: {{ $c->valida_hasta?->format('d/m/Y') ?? '—' }}<br>
            @if ($vendedor)Vendedor: {{ $vendedor }}@endif
        </td>
    </tr>
</table>

<div class="caja" style="margin-top: 5mm;">
    <strong>Cliente:</strong> {{ $c->cliente->razon_social }}
    @if ($c->cliente->identificacion_fiscal) · RTN {{ $c->cliente->identificacion_fiscal }}@endif<br>
    @if ($c->sucursal)<strong>Sucursal:</strong> {{ $c->sucursal->nombre }}@if ($c->sucursal->direccion) · {{ $c->sucursal->direccion }}@endif @endif
</div>

<table>
    <thead>
        <tr><th>Código</th><th>Descripción</th><th class="r">Cant.</th><th>Unidad</th><th class="r">Valor unitario</th><th class="r">Total</th></tr>
    </thead>
    <tbody>
    @foreach ($c->lineas as $l)
        <tr>
            <td>{{ $l->codigo }}</td>
            <td>{{ $l->descripcion }}</td>
            <td class="r">{{ rtrim(rtrim(number_format((float) $l->cantidad, 2, '.', ''), '0'), '.') }}</td>
            <td>{{ $l->unidad }}</td>
            <td class="r">L {{ number_format((float) $l->precio_unitario, 2) }}</td>
            <td class="r">L {{ number_format((float) $l->importe, 2) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<table style="width: 60%; margin: 4mm 0 4mm auto;">
    <tr><td>Servicios</td><td class="r">L {{ number_format((float) $c->subtotal_servicios, 2) }}</td></tr>
    @if ((float) $c->ajuste_minimo > 0)
        <tr><td>Ajuste a servicio mínimo por visita</td><td class="r">L {{ number_format((float) $c->ajuste_minimo, 2) }}</td></tr>
    @endif
    @if ((float) $c->recargos_monto > 0)
        <tr><td>Recargos ({{ $recargos->implode(', ') }})</td><td class="r">L {{ number_format((float) $c->recargos_monto, 2) }}</td></tr>
    @endif
    @if ((float) $c->descuento_monto > 0)
        <tr><td>Descuento ({{ $descuento }})</td><td class="r">− L {{ number_format((float) $c->descuento_monto, 2) }}</td></tr>
    @endif
    @if ((float) $c->repuestos_monto > 0)
        <tr><td>Repuestos y materiales</td><td class="r">L {{ number_format((float) $c->repuestos_monto, 2) }}</td></tr>
    @endif
    @if ((float) $c->logistica_monto > 0 || (float) $c->viaticos_monto > 0)
        @php($lg = $c->logistica_detalle ?? [])
        @if ((float) $c->logistica_monto > 0)
            <tr><td>Movilización ({{ rtrim(rtrim(number_format((float) ($lg['km'] ?? 0), 2, '.', ''), '0'), '.') }} km × L {{ number_format((float) ($lg['tarifa_km'] ?? 0), 2) }})</td><td class="r">L {{ number_format((float) $c->logistica_monto, 2) }}</td></tr>
        @endif
        @if ((float) ($lg['alimentacion'] ?? 0) > 0)
            <tr><td>Alimentación ({{ $lg['tecnicos'] ?? 0 }} técnicos × {{ $lg['dias'] ?? 0 }} días × L {{ number_format((float) ($lg['tarifa_alimentacion'] ?? 0), 2) }})</td><td class="r">L {{ number_format((float) $lg['alimentacion'], 2) }}</td></tr>
        @endif
        @if ((float) ($lg['hospedaje'] ?? 0) > 0)
            <tr><td>Hospedaje ({{ $lg['tecnicos'] ?? 0 }} técnicos × {{ $lg['noches'] ?? 0 }} noches × L {{ number_format((float) ($lg['tarifa_hospedaje'] ?? 0), 2) }})</td><td class="r">L {{ number_format((float) $lg['hospedaje'], 2) }}</td></tr>
        @endif
        @if ((float) ($lg['peajes'] ?? 0) > 0)
            <tr><td>Peajes y estacionamiento</td><td class="r">L {{ number_format((float) $lg['peajes'], 2) }}</td></tr>
        @endif
    @endif
    @if ($c->visita_tratamiento === 'cobrar' && (float) $c->visita_monto > 0)
        <tr><td>Visita técnica</td><td class="r">L {{ number_format((float) $c->visita_monto, 2) }}</td></tr>
    @elseif (in_array($c->visita_tratamiento, ['bonificar', 'gratis'], true))
        <tr><td>Visita técnica (sin costo)</td><td class="r">L 0.00</td></tr>
    @endif
    <tr><td>Subtotal</td><td class="r">L {{ number_format((float) $c->subtotal, 2) }}</td></tr>
    <tr><td>ISV ({{ (int) (config('tarifario.isv') * 100) }} %)</td><td class="r">L {{ number_format((float) $c->isv, 2) }}</td></tr>
    <tr class="total"><td>TOTAL</td><td class="r">L {{ number_format((float) $c->total, 2) }}</td></tr>
    @if ((int) $c->visitas_anuales > 1)
        @php($anual = \App\Services\LogisticaViaticos::anual((float) $c->subtotal, (int) $c->visitas_anuales, (float) config('tarifario.isv')))
        <tr><td>Visitas al año</td><td class="r">{{ (int) $c->visitas_anuales }}</td></tr>
        <tr><td>Subtotal anual</td><td class="r">L {{ number_format($anual['subtotal'], 2) }}</td></tr>
        <tr><td>ISV anual ({{ (int) (config('tarifario.isv') * 100) }} %)</td><td class="r">L {{ number_format($anual['isv'], 2) }}</td></tr>
        <tr class="total"><td>TOTAL ANUAL</td><td class="r">L {{ number_format($anual['total'], 2) }}</td></tr>
    @endif
</table>

<strong>Condiciones</strong>
<ol>
@foreach (preg_split('/\R/', (string) $c->condiciones, -1, PREG_SPLIT_NO_EMPTY) as $linea)
    <li>{{ $linea }}</li>
@endforeach
</ol>

@php($firmada = $c->estado === 'aprobada' && filled($c->firma_cliente) && str_starts_with((string) $c->firma_cliente, 'data:image/png;base64,'))

<table class="sin-borde" style="margin-top: 14mm;">
    <tr>
        <td class="firma">Elaborado por {{ $empresa['nombre'] }}</td>
        <td style="width: 10%;"></td>
        <td class="firma">
            @if ($firmada)
                <img src="{{ $c->firma_cliente }}" style="height: 16mm; max-width: 100%;"><br>
                {{ $c->aprobada_por_nombre }}@if ($c->aprobada_por_cargo) · {{ $c->aprobada_por_cargo }}@endif
            @else
                Aceptación del cliente (firma y sello)
            @endif
        </td>
    </tr>
</table>

@if ($firmada)
<div class="caja" style="margin-top: 6mm;">
    <strong>Aceptación digital</strong><br>
    <span class="muted">
        Aceptada por {{ $c->aprobada_por_nombre }}@if ($c->aprobada_por_cargo) ({{ $c->aprobada_por_cargo }})@endif
        el {{ $c->aprobada_en?->format('d/m/Y H:i') }}<br>
        IP: {{ $c->aceptada_ip ?? '—' }}<br>
        Huella del documento (SHA-256): <span style="font-size: 7pt;">{{ $c->hash_documento }}</span>
    </span>
</div>
@endif
</body>
</html>
