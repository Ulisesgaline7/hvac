@php
    use App\Enums\EstadoEquipo;
    use App\Enums\EstadoOt;
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Facades\DB;

    $esStaff = auth()->check() && auth()->user()->hasAnyRole(['administrador', 'coordinador']);

    $ordenes = \App\Models\OrdenTrabajo::query()
        ->whereIn('id', DB::table('orden_trabajo_equipo')->where('equipo_id', $equipo->id)->select('orden_trabajo_id'))
        ->when(! $esStaff, fn ($q) => $q->where('estado', EstadoOt::Cerrada))
        ->with('tecnico.user')
        ->latest('id')->limit(10)->get();

    $abierta = $ordenes->first(fn ($o) => ! in_array($o->estado, [EstadoOt::Cerrada, EstadoOt::Cancelada], true));
    $ultima = $ordenes->first();
    $tecNombre = ($abierta ?? $ultima)?->tecnico?->user?->name;

    $etiquetasEstado = [
        'operativo' => 'Operativo',
        'con_observaciones' => 'Con observaciones',
        'fuera_de_servicio' => 'Fuera de servicio',
        'dado_de_baja' => 'Dado de baja',
    ];
    $actual = $equipo->estado->value;
    $estadosMostrar = collect(EstadoEquipo::cases())
        ->filter(fn ($c) => $c !== EstadoEquipo::DadoDeBaja || $c->value === $actual);

    $fmt = fn ($f) => $f ? Carbon::parse($f)->format('d/m/Y') : '—';

    // Garantía: la tabla garantias manda; la columna del equipo es respaldo.
    $garantia = \App\Models\Garantia::where('equipo_id', $equipo->id)->orderByDesc('hasta')->first();
    $garHasta = $garantia?->hasta ?? $equipo->garantia_hasta;
    $garVigente = $garHasta && Carbon::parse($garHasta)->endOfDay()->isFuture();

    // Próximo servicio: plan preventivo del contrato vigente; si no, OT abierta.
    $proxPlan = DB::table('contrato_equipo as p')
        ->join('contratos as c', 'c.id', '=', 'p.contrato_id')
        ->where('p.equipo_id', $equipo->id)
        ->whereNull('c.deleted_at')
        ->whereDate('c.vigencia_fin', '>=', now()->toDateString())
        ->whereNotNull('p.proxima_fecha')
        ->min('p.proxima_fecha');
    $proximo = $proxPlan ?: $abierta?->fecha_programada;

    $tel = preg_replace('/\D/', '', (string) config('etiquetas.whatsapp'));
    $msg = rawurlencode("Hola, necesito servicio para el equipo {$equipo->codigo}.");
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>{{ $equipo->codigo }}</title>
<style>
    :root { --azul:#1f6fd6; --ink:#0b1f3a; --gris:#6b7280; --linea:#e5e7eb; --verde:#16a34a; --ambar:#d97706; --rojo:#dc2626; }
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
    .fila { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    .estados { display: grid; grid-template-columns: repeat(3, 1fr); gap: .5rem; }
    .est { border: 1px solid var(--linea); border-radius: 8px; padding: .65rem .3rem; text-align: center; font-size: .78rem; color: var(--gris); }
    .est.on { background: var(--azul); border-color: var(--azul); color: #fff; font-weight: 600; }
    .est.on.warn { background: var(--ambar); border-color: var(--ambar); }
    .est.on.bad { background: var(--rojo); border-color: var(--rojo); }
    .avatar { width: 34px; height: 34px; border-radius: 50%; background: var(--ink); color: #fff; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: .85rem; vertical-align: middle; margin-right: .5rem; }
    .chip { display: inline-block; font-size: .72rem; font-weight: 600; padding: .18rem .55rem; border-radius: 4px; margin: 0 .35rem .35rem 0; color: #fff; background: var(--gris); }
    .c-azul { background: var(--azul); } .c-verde { background: var(--verde); } .c-ambar { background: var(--ambar); } .c-rojo { background: var(--rojo); }
    .datos { display: grid; grid-template-columns: 1fr 1fr; gap: .9rem 1rem; }
    .t { display: flex; gap: .7rem; margin-bottom: 1rem; }
    .t .ini { width: 36px; height: 36px; border-radius: 50%; background: var(--azul); color: #fff; display: flex; align-items: center; justify-content: center; font-size: .8rem; font-weight: 700; flex: none; }
    .t b { font-size: .92rem; } .t small { color: var(--gris); margin-left: .3rem; }
    .t p { margin: .2rem 0 0; font-size: .88rem; color: #374151; }
    .btn { display: block; text-align: center; padding: .9rem; border-radius: 10px; text-decoration: none; font-weight: 600; margin-top: .6rem; background: var(--azul); color: #fff; }
    .btn.sec { background: #e8eef7; color: var(--ink); }
    button.est { width: 100%; font: inherit; cursor: pointer; background: #fff; }
</style>
</head>
<body>
<header><img src="{{ \App\Support\DocumentoMarca::logo() }}" alt=""></header>
<main>
    <h1>{{ $equipo->codigo }} · {{ $equipo->tipo->nombre }}</h1>
    <p class="sub">{{ $equipo->area->sucursal->cliente->razon_social }}</p>
    @if (session('ok'))<span class="chip c-verde">{{ session('ok') }}</span>@endif

    <div class="seccion">
        <div class="lbl">Estado del equipo</div>
        <div class="estados">
            @foreach ($estadosMostrar as $c)
                @php
                    $extra = $c->value === $actual
                        ? match ($c) { EstadoEquipo::ConObservaciones => 'warn', EstadoEquipo::FueraDeServicio, EstadoEquipo::DadoDeBaja => 'bad', default => '' }
                        : '';
                @endphp
                @if ($esStaff && $c->value !== $actual && $actual !== 'dado_de_baja' && $c !== EstadoEquipo::DadoDeBaja)
                    <form method="POST" action="{{ route('equipo.estado', $equipo->token_qr) }}" style="margin:0" onsubmit="return confirm('¿Cambiar el estado a {{ $etiquetasEstado[$c->value] }}?')">@csrf<input type="hidden" name="estado" value="{{ $c->value }}"><button type="submit" class="est">{{ $etiquetasEstado[$c->value] }}</button></form>
                @else
                    <div class="est {{ $c->value === $actual ? 'on' : '' }} {{ $extra }}">{{ $etiquetasEstado[$c->value] }}</div>
                @endif
            @endforeach
        </div>
    </div>

    <div class="seccion fila">
        <div>
            <div class="lbl">{{ $proxPlan ? 'Próximo mantenimiento' : 'Próxima visita programada' }}</div>
            <div class="val">{{ $fmt($proximo) }}</div>
        </div>
        <div>
            <div class="lbl">Responsable</div>
            <div class="val">
                @if ($tecNombre)
                    <span class="avatar">{{ mb_strtoupper(mb_substr($tecNombre, 0, 1)) }}</span>{{ $tecNombre }}
                @else — @endif
            </div>
        </div>
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

    <div class="seccion fila">
        <div>
            <div class="lbl">Categorías</div>
            <span class="chip c-azul">{{ $equipo->tipo->nombre }}</span>
            @if ($garHasta)
                <span class="chip {{ $garVigente ? 'c-verde' : 'c-rojo' }}">{{ $garVigente ? 'En garantía' : 'Garantía vencida' }}</span>
            @endif
        </div>
        <div>
            <div class="lbl">Garantía hasta</div>
            <div class="val">{{ $fmt($garHasta) }}</div>
        </div>
    </div>

    @php
        $fotos = \Spatie\MediaLibrary\MediaCollections\Models\Media::query()
            ->where('model_type', \App\Models\OrdenTrabajoEquipo::class)
            ->whereIn('model_id', DB::table('orden_trabajo_equipo')->where('equipo_id', $equipo->id)
                ->whereIn('orden_trabajo_id', $ordenes->pluck('id'))->select('id'))
            ->where('collection_name', 'like', '%foto%')
            ->where('collection_name', 'not like', '%firma%')
            ->latest('id')->limit(6)->get();
    @endphp
    @if ($fotos->isNotEmpty())
        <div class="seccion">
            <div class="lbl" style="font-size:1rem;color:var(--ink);font-weight:700;">Archivos</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem">
                @foreach ($fotos as $f)
                    <a href="{{ route('equipo.foto', [$equipo->token_qr, $f->id]) }}" target="_blank" rel="noopener">
                        <img src="{{ route('equipo.foto', [$equipo->token_qr, $f->id]) }}" alt="" loading="lazy" style="width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:8px;border:1px solid var(--linea)">
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <div class="seccion">
        <div class="lbl" style="font-size:1rem;color:var(--ink);font-weight:700;">Historial de servicios</div>
        @forelse ($ordenes as $o)
            @php
                $col = match ($o->estado) { EstadoOt::Cerrada => 'c-verde', EstadoOt::Cancelada => 'c-rojo', EstadoOt::EnEspera => 'c-ambar', default => 'c-azul' };
                $nom = $o->tecnico?->user?->name ?? 'T';
                $texto = $o->diagnostico ?: $o->descripcion;
            @endphp
            <div class="t">
                <div class="ini">{{ mb_strtoupper(mb_substr($nom, 0, 1)) }}</div>
                <div>
                    <b>{{ $o->numero }}</b><small>{{ $fmt($o->cerrada_en ?? $o->fecha_programada ?? $o->created_at) }}</small>
                    <div style="margin-top:.2rem">
                        <span class="chip" style="background:var(--gris)">{{ $o->tipo->getLabel() }}</span>
                        <span class="chip {{ $col }}">{{ $o->estado->getLabel() }}</span>
                    </div>
                    @if ($texto)<p>{{ \Illuminate\Support\Str::limit($texto, 140) }}</p>@endif
                </div>
            </div>
        @empty
            <p class="sub">Aún no hay servicios registrados para este equipo.</p>
        @endforelse
    </div>

    @if ($tel)<a class="btn" href="https://wa.me/{{ $tel }}?text={{ $msg }}">Solicitar servicio</a>@endif
    @if ($esStaff && $abierta)
        <a class="btn sec" href="/admin/ordenes-trabajo/{{ $abierta->id }}/edit">Abrir OT {{ $abierta->numero }}</a>
    @endif
</main>
</body>
</html>
