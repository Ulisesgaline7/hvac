<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $equipo->codigo }}</title>
<style>
    body { font-family: system-ui, sans-serif; margin: 0; background: #f4f5f7; color: #1f2937; }
    main { max-width: 560px; margin: 0 auto; padding: 1.25rem; }
    h1 { font-size: 1.6rem; margin: 0 0 .25rem; }
    .estado { display: inline-block; padding: .15rem .6rem; border-radius: 999px; background: #e5e7eb; font-size: .85rem; }
    dl { background: #fff; border-radius: 10px; padding: 1rem; margin-top: 1rem; }
    dt { font-size: .75rem; color: #6b7280; margin-top: .75rem; }
    dt:first-child { margin-top: 0; }
    dd { margin: 0; font-size: 1.05rem; }
</style>
</head>
<body>
<main>
    <h1>{{ $equipo->codigo }}</h1>
    <span class="estado">{{ str_replace('_', ' ', $equipo->estado->value) }}</span>
    <dl>
        <dt>Cliente</dt><dd>{{ $equipo->area->sucursal->cliente->razon_social }}</dd>
        <dt>Sucursal</dt><dd>{{ $equipo->area->sucursal->nombre }}</dd>
        <dt>Área</dt><dd>{{ $equipo->area->nombre }}</dd>
        <dt>Tipo</dt><dd>{{ $equipo->tipo->nombre }}</dd>
        @if ($equipo->marca)<dt>Marca</dt><dd>{{ $equipo->marca->nombre }} {{ $equipo->modelo }}</dd>@endif
        @if ($equipo->serie)<dt>Serie</dt><dd>{{ $equipo->serie }}</dd>@endif
        @if ($equipo->capacidad_valor)<dt>Capacidad</dt><dd>{{ $equipo->capacidad_valor + 0 }} {{ $equipo->capacidad_unidad }}</dd>@endif
        @if ($equipo->refrigerante)<dt>Refrigerante</dt><dd>{{ $equipo->refrigerante->nombre }}</dd>@endif
        @if ($equipo->fecha_instalacion)<dt>Instalación</dt><dd>{{ $equipo->fecha_instalacion->format('d/m/Y') }}</dd>@endif
        @if ($equipo->garantia_hasta)<dt>Garantía hasta</dt><dd>{{ $equipo->garantia_hasta->format('d/m/Y') }}</dd>@endif
    </dl>
</main>
</body>
</html>
