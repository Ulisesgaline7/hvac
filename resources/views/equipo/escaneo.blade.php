<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>{{ $equipo->codigo }}</title>
<style>
    body { font-family: system-ui, sans-serif; margin: 0; background: #f4f5f7; color: #0b1f3a; }
    .tarjeta { max-width: 420px; margin: 8vh auto; background: #fff; border-radius: 14px; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
    h1 { margin: 0 0 4px; font-size: 1.6rem; }
    .sub { color: #5b6b82; margin: 0 0 18px; }
    dl { margin: 0 0 20px; } dt { font-size: .75rem; color: #5b6b82; text-transform: uppercase; }
    dd { margin: 0 0 10px; font-weight: 600; }
    .btn { display: block; text-align: center; padding: 14px; border-radius: 10px; text-decoration: none; font-weight: 600; margin-bottom: 10px; }
    .primario { background: #1f6fd6; color: #fff; }
    .secundario { background: #e8eef7; color: #0b1f3a; }
</style>
</head>
<body>
<div class="tarjeta">
    <h1>{{ $equipo->codigo }}</h1>
    <p class="sub">{{ $equipo->tipo->nombre }}</p>
    <dl>
        <dt>Marca</dt><dd>{{ $equipo->marca->nombre ?? '—' }}</dd>
        <dt>Modelo</dt><dd>{{ $equipo->modelo ?: '—' }}</dd>
    </dl>
    @php
        $tel = preg_replace('/\D/', '', (string) config('etiquetas.whatsapp'));
        $msg = rawurlencode("Hola, necesito servicio para el equipo {$equipo->codigo}.");
    @endphp
    @if ($tel)
        <a class="btn primario" href="https://wa.me/{{ $tel }}?text={{ $msg }}">Solicitar servicio por WhatsApp</a>
    @endif
    <a class="btn secundario" href="{{ route('equipo.ingreso') }}">Iniciar sesión para ver la ficha completa</a>
</div>
</body>
</html>
