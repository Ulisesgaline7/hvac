<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 0; }
    body { margin: 0; font-family: 'DejaVu Sans', sans-serif; }
    .pagina { width: {{ $ancho }}mm; height: {{ $alto }}mm; box-sizing: border-box; padding: 3mm; position: relative; overflow: hidden; }
    .salto { page-break-after: always; }
    .qr { position: absolute; top: 3mm; right: 3mm; width: {{ min($alto - 6, 32) }}mm; height: {{ min($alto - 6, 32) }}mm; }
    .texto { margin-right: {{ min($alto - 6, 32) + 3 }}mm; }
    .codigo { font-size: 17pt; font-weight: bold; margin: 0 0 1.5mm 0; }
    .linea { font-size: 7pt; margin: 0 0 0.8mm 0; line-height: 1.2; }
    .etq { color: #555; }
    .pie { position: absolute; bottom: 2mm; left: 3mm; font-size: 5.5pt; color: #666; }
</style>
</head>
<body>
@foreach ($etiquetas as $e)
    @php($eq = $e['equipo'])
    <div class="pagina {{ $loop->last ? '' : 'salto' }}">
        <img class="qr" src="{{ $e['qr'] }}">
        <div class="texto">
            <p class="codigo">{{ $eq->codigo }}</p>
            <p class="linea"><span class="etq">Cliente:</span> {{ $eq->area->sucursal->cliente->razon_social }}</p>
            <p class="linea"><span class="etq">Sucursal:</span> {{ $eq->area->sucursal->nombre }}</p>
            <p class="linea"><span class="etq">Área:</span> {{ $eq->area->nombre }}</p>
            <p class="linea"><span class="etq">Tipo:</span> {{ $eq->tipo->nombre }}</p>
            @if ($eq->capacidad_valor)
                <p class="linea"><span class="etq">Capacidad:</span> {{ rtrim(rtrim(number_format($eq->capacidad_valor, 2, '.', ''), '0'), '.') }} {{ $eq->capacidad_unidad }}</p>
            @endif
        </div>
        <div class="pie">Escanee el QR para ver la ficha del equipo</div>
    </div>
@endforeach
</body>
</html>
