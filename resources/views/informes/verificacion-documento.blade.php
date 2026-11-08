<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Verificación de documento</title>
<style>
    body { font-family: system-ui, sans-serif; background: #F4F8FF; color: #082A4A; margin: 0; padding: 24px; }
    .tarjeta { max-width: 420px; margin: 40px auto; background: #fff; border-top: 4px solid {{ $color }}; padding: 24px; border-radius: 6px; }
    h1 { font-size: 1.1rem; margin: 0 0 12px; color: {{ $color }}; }
    dt { font-size: .7rem; text-transform: uppercase; color: #4B5A6E; margin-top: 10px; }
    dd { margin: 2px 0 0; font-weight: 600; }
    .nota { font-size: .8rem; color: #4B5A6E; margin-top: 16px; }
</style>
</head>
<body>
    <div class="tarjeta">
        <h1>{{ $estado === 'Anulado' ? '✖' : '✔' }} {{ $tipo }} {{ $estado === 'Vigente' ? 'auténtico y vigente' : strtolower($estado) }}</h1>
        <dl>
            <dt>Número</dt><dd>{{ $numero }}</dd>
            <dt>Cliente</dt><dd>{{ $cliente }}</dd>
            <dt>Fecha</dt><dd>{{ $fecha->format('d/m/Y') }}</dd>
            @if ($vence)
                <dt>{{ $tipo === 'Garantía' ? 'Vigente hasta' : 'Vence' }}</dt><dd>{{ $vence->format('d/m/Y') }}</dd>
            @endif
            <dt>Estado</dt><dd style="color: {{ $color }}">{{ $estado }}</dd>
        </dl>
        @if ($estado === 'Anulado')
            <p class="nota">Este documento fue anulado. No lo considere válido y comuníquese con Control Total.</p>
        @endif
    </div>
</body>
</html>
