<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Verificación de informe</title>
<style>
    body { font-family: system-ui, sans-serif; background: #F4F8FF; color: #082A4A; margin: 0; padding: 24px; }
    .tarjeta { max-width: 420px; margin: 40px auto; background: #fff; border-top: 4px solid #15803d; padding: 24px; border-radius: 6px; }
    h1 { font-size: 1.1rem; margin: 0 0 12px; color: #15803d; }
    dt { font-size: .7rem; text-transform: uppercase; color: #4B5A6E; margin-top: 10px; }
    dd { margin: 2px 0 0; font-weight: 600; }
</style>
</head>
<body>
    <div class="tarjeta">
        <h1>✔ Informe auténtico</h1>
        <dl>
            <dt>Orden de trabajo</dt><dd>{{ $numero }}</dd>
            <dt>Cliente</dt><dd>{{ $cliente }}</dd>
            <dt>Publicado</dt><dd>{{ $publicado->format('d/m/Y H:i') }}</dd>
        </dl>
    </div>
</body>
</html>
