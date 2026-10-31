<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Ingresar</title>
<style>
    body { font-family: system-ui, sans-serif; display: grid; place-items: center; min-height: 100vh; margin: 0; background: #f4f5f7; color: #1f2937; }
    main { width: 100%; max-width: 360px; padding: 1.25rem; text-align: center; }
    a { display: block; margin-top: .75rem; padding: .9rem; border-radius: 10px; background: #087BFF; color: #fff; text-decoration: none; font-size: 1.05rem; }
    a.sec { background: #fff; color: #1f2937; border: 1px solid #d1d5db; }
</style>
</head>
<body>
<main>
    <h1>¿Cómo ingresa?</h1>
    <p>Inicie sesión para ver la información de este equipo.</p>
    @foreach ($opciones as $o)
        <a href="{{ $o['url'] }}" class="{{ $loop->first ? '' : 'sec' }}">{{ $o['nombre'] }}</a>
    @endforeach
</main>
</body>
</html>
