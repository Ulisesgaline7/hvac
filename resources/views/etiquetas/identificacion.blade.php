<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 0; }
    body { margin: 0; font-family: 'DejaVu Sans', sans-serif; color: #0b1f3a; }
    .pagina { position: relative; width: {{ $ancho }}mm; height: {{ $alto - 1 }}mm; overflow: hidden; background: #fff; }
    .salto { page-break-after: always; }
    .izq { position: absolute; left: 3mm; top: 3mm; }
    .logo { height: 9mm; }
    table.datos { border-collapse: collapse; margin-top: 2mm; }
    table.datos td { font-size: 7pt; padding: 0.9mm 1.5mm; border-bottom: 0.2mm solid #0b1f3a; }
    table.datos td.k { background: #0b1f3a; color: #fff; width: 15mm; }
    .qr-caja { position: absolute; top: 3mm; right: 3mm; background: #fff; border: 0.6mm solid #1f6fd6; }
    .qr-caja img { display: block; margin: 1mm; }
    .qr-texto { position: absolute; right: 3mm; text-align: center; font-size: 5.5pt; font-weight: bold; line-height: 1.25; }
    .pie { position: absolute; left: 0; bottom: 0; width: {{ $ancho }}mm; height: 9mm; background: #0b1f3a; color: #fff; }
    .pie td { font-size: 6pt; vertical-align: middle; height: 9mm; white-space: nowrap; }
    .pie td.id { text-align: right; font-size: 9pt; font-weight: bold; padding-right: 4mm; width: 32mm; }
</style>
</head>
<body>
@foreach ($etiquetas as $e)
    @php
        $eq = $e['equipo'];
        $qrCaja = min($alto - 14, 34);
        $qrImg  = $qrCaja - 1.2 - 2;
        $izq    = $ancho - $qrCaja - 12;
        $serie  = data_get($eq, 'numero_serie') ?? data_get($eq, 'serial') ?? data_get($eq, 'serie');
        $ubic   = trim(($eq->area->sucursal->nombre ?? '').' · '.($eq->area->nombre ?? ''), ' ·');
    @endphp
    <div class="pagina {{ $loop->last ? '' : 'salto' }}">
        <div class="izq" style="width: {{ $izq }}mm;">
            <img class="logo" src="{{ \App\Support\DocumentoMarca::logo() }}">
            <table class="datos" style="width: {{ $izq }}mm;">
                <tr><td class="k">Equipo</td><td>{{ $eq->tipo->nombre }}</td></tr>
                <tr><td class="k">Marca</td><td>{{ $eq->marca->nombre ?? '—' }}</td></tr>
                <tr><td class="k">Modelo</td><td>{{ $eq->modelo ?: '—' }}</td></tr>
                @if ($serie)<tr><td class="k">Serial</td><td>{{ $serie }}</td></tr>@endif
                <tr><td class="k">Ubicación</td><td>{{ $ubic }}</td></tr>
            </table>
        </div>
        <div class="qr-caja" style="width: {{ $qrCaja - 1.2 }}mm; height: {{ $qrCaja - 1.2 }}mm;">
            <img src="{{ $e['qr'] }}" style="width: {{ $qrImg }}mm; height: {{ $qrImg }}mm;">
        </div>
        <div class="qr-texto" style="top: {{ 3 + $qrCaja + 1.5 }}mm; width: {{ $qrCaja }}mm;">
            ESCANEE AQUÍ<br>ficha, historial y servicio
        </div>
        <table class="pie" cellspacing="0" cellpadding="0"><tr>
            <td style="padding-left: 4mm;">Mantenimiento · Servicio técnico · Soporte</td>
            <td class="id">ID: {{ $eq->codigo }}</td>
        </tr></table>
    </div>
@endforeach
</body>
</html>
