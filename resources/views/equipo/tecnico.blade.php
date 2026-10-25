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
    h2 { font-size: 1rem; margin: 1.25rem 0 .5rem; }
    .estado { display: inline-block; padding: .15rem .6rem; border-radius: 999px; background: #e5e7eb; font-size: .85rem; }
    dl, .ot { background: #fff; border-radius: 10px; padding: 1rem; margin-top: 1rem; }
    .ot { margin-top: .5rem; }
    dt { font-size: .75rem; color: #6b7280; margin-top: .75rem; }
    dt:first-child { margin-top: 0; }
    dd { margin: 0; font-size: 1.05rem; }
    .aviso { font-size: .9rem; color: #6b7280; margin-top: 1rem; }
</style>
</head>
<body>
<main>
    <h1>{{ $equipo->codigo }}</h1>
    <span class="estado">{{ str_replace('_', ' ', $equipo->estado->value) }}</span>
    <dl>
        <dt>Tipo</dt><dd>{{ $equipo->tipo->nombre }}</dd>
        @if ($equipo->marca)<dt>Marca</dt><dd>{{ $equipo->marca->nombre }} {{ $equipo->modelo }}</dd>@endif
        @if ($completa)
            <dt>Cliente</dt><dd>{{ $equipo->area->sucursal->cliente->razon_social }}</dd>
            <dt>Sucursal</dt><dd>{{ $equipo->area->sucursal->nombre }}</dd>
            <dt>Área</dt><dd>{{ $equipo->area->nombre }}</dd>
            @if ($equipo->serie)<dt>Serie</dt><dd>{{ $equipo->serie }}</dd>@endif
            @if ($equipo->capacidad_valor)<dt>Capacidad</dt><dd>{{ $equipo->capacidad_valor + 0 }} {{ $equipo->capacidad_unidad }}</dd>@endif
            @if ($equipo->refrigerante)<dt>Refrigerante</dt><dd>{{ $equipo->refrigerante->nombre }}</dd>@endif
        @endif
    </dl>
    @if ($completa)
        <h2>Mis órdenes de trabajo con este equipo</h2>
        @foreach ($ordenes as $o)
            <div class="ot"><strong>{{ $o['numero'] }}</strong> · {{ $o['tipo'] }}<br>
                {{ $o['estado'] }}@if ($o['fecha']) · {{ $o['fecha'] }}@endif · Mi rol: {{ $o['rol'] }}</div>
        @endforeach
    @else
        <p class="aviso">Este equipo no está en ninguna de sus órdenes de trabajo.</p>
    @endif
</main>
</body>
</html>
