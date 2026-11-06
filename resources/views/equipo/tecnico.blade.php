@php
    $fmt = fn ($f) => $f ? \Illuminate\Support\Carbon::parse($f)->format('d/m/Y') : '—';
    $etiquetasEstado = ['operativo' => 'Operativo', 'con_observaciones' => 'Con observaciones', 'fuera_de_servicio' => 'Fuera de servicio', 'dado_de_baja' => 'Dado de baja'];
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>{{ $equipo->codigo }}</title>
<style>
    :root { --azul:#1f6fd6; --ink:#0b1f3a; --gris:#6b7280; --linea:#e5e7eb; --verde:#16a34a; }
    * { box-sizing: border-box; }
    body { font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; margin: 0; background: #fff; color: var(--ink); }
    header { background: #f3f6fb; border-bottom: 4px solid var(--azul); padding: .8rem 1.25rem; }
    header img { height: 32px; display: block; }
    main { max-width: 560px; margin: 0 auto; padding: 1.25rem; }
    h1 { font-size: 1.25rem; margin: 0 0 .15rem; }
    .sub { color: var(--gris); margin: 0 0 1rem; font-size: .92rem; }
    .seccion { border-top: 1px solid var(--linea); padding: 1rem 0; }
    .lbl { font-size: .8rem; color: var(--gris); margin-bottom: .4rem; }
    .val { font-size: 1rem; }
    .datos { display: grid; grid-template-columns: 1fr 1fr; gap: .9rem 1rem; }
    .chip { display: inline-block; font-size: .72rem; font-weight: 600; padding: .18rem .55rem; border-radius: 4px; margin: 0 .35rem .35rem 0; color: #fff; background: var(--gris); }
    .c-azul { background: var(--azul); } .c-verde { background: var(--verde); }
    .t { margin-bottom: 1rem; } .t b { font-size: .92rem; } .t small { color: var(--gris); margin-left: .3rem; }
    .aviso { background: #f3f6fb; border-radius: 8px; padding: .8rem; font-size: .88rem; color: #374151; }
</style>
</head>
<body>
<header><img src="{{ \App\Support\DocumentoMarca::logo() }}" alt=""></header>
<main>
    <h1>{{ $equipo->codigo }} · {{ $equipo->tipo->nombre }}</h1>
    @if ($completa)
        <p class="sub">{{ $equipo->area->sucursal->cliente->razon_social }}</p>

        <div class="seccion">
            <div class="lbl">Estado del equipo</div>
            <span class="chip c-azul">{{ $etiquetasEstado[$equipo->estado->value] ?? $equipo->estado->value }}</span>
        </div>

        <div class="seccion">
            <div class="lbl">Ubicación</div>
            <div class="val">{{ $equipo->area->sucursal->nombre }} › {{ $equipo->area->nombre }}</div>
        </div>

        <div class="seccion">
            <div class="lbl">Descripción</div>
            <div class="datos">
                <div><div class="lbl">Marca</div><div class="val">{{ $equipo->marca->nombre ?? '—' }}</div></div>
                <div><div class="lbl">Modelo</div><div class="val">{{ $equipo->modelo ?: '—' }}</div></div>
                <div><div class="lbl">Serie</div><div class="val">{{ $equipo->serie ?: '—' }}</div></div>
                <div><div class="lbl">Refrigerante</div><div class="val">{{ $equipo->refrigerante->nombre ?? '—' }}</div></div>
                <div><div class="lbl">Capacidad</div><div class="val">{{ $equipo->capacidad_valor ? ($equipo->capacidad_valor + 0).' '.$equipo->capacidad_unidad : '—' }}</div></div>
                <div><div class="lbl">Instalación</div><div class="val">{{ $fmt($equipo->fecha_instalacion) }}</div></div>
            </div>
        </div>
    @else
        <p class="sub">{{ $equipo->marca->nombre ?? '' }} {{ $equipo->modelo }}</p>
        <div class="aviso">Este equipo no está en ninguna de tus órdenes de trabajo. Solo puedes ver su identificación.</div>
    @endif

    <div class="seccion">
        <div class="lbl" style="font-size:1rem;color:var(--ink);font-weight:700;">Tus órdenes de trabajo</div>
        @forelse ($ordenes as $o)
            <div class="t">
                <b>{{ $o['numero'] }}</b><small>{{ $o['fecha'] ?? '—' }}</small>
                <div style="margin-top:.2rem">
                    <span class="chip">{{ ucfirst($o['tipo']) }}</span>
                    <span class="chip c-azul">{{ $o['estado'] }}</span>
                    <span class="chip c-verde">{{ $o['rol'] }}</span>
                </div>
            </div>
        @empty
            <p class="sub">No tienes órdenes para este equipo.</p>
        @endforelse
    </div>
</main>
</body>
</html>
