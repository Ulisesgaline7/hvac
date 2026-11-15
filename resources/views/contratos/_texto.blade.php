@php
    $tipo = $c->tipo instanceof \Filament\Support\Contracts\HasLabel ? $c->tipo->getLabel() : (is_object($c->tipo) ? $c->tipo->value : $c->tipo);
    $f = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('d/m/Y') : '—';
    $empresa = \App\Support\DocumentoMarca::todo()['empresa_nombre'] ?? 'La Empresa';
    $tipoVal = is_object($c->tipo) ? $c->tipo->value : (string) $c->tipo;
    $sufijoTipo = $tipoVal === 'mixto' ? '' : ' (' . mb_strtolower((string) $tipo) . ')';
@endphp
<div class="seccion">Partes</div>
<p>Entre <strong>{{ $empresa }}</strong> («la Empresa») y <strong>{{ $c->cliente->razon_social ?? '' }}</strong> («el Cliente»), se acuerda el presente contrato de servicio{{ $sufijoTipo }}.</p>

<div class="seccion">Vigencia</div>
@if($c->periodicidad === 'unica')
<p>Este contrato ampara un servicio en una sola visita, que la Empresa coordinará con el Cliente entre el {{ $f($c->vigencia_inicio) }} y el {{ $f($c->vigencia_fin) }}. La garantía del trabajo corre por separado, desde el cierre de la orden de trabajo (ver Garantías). Esta vigencia no limita al Cliente para contratar servicios adicionales en cualquier momento.</p>
@else
<p>Del {{ $f($c->vigencia_inicio) }} al {{ $f($c->vigencia_fin) }}.</p>
@endif

@if(filled($c->servicios_incluidos))
<div class="seccion">Servicios incluidos</div>
<p>{!! nl2br(e($c->servicios_incluidos)) !!}</p>
@endif

@if(filled($c->exclusiones))
<div class="seccion">Exclusiones</div>
<p>{!! nl2br(e($c->exclusiones)) !!}</p>
@endif

@php
    $catsGar = collect(config('tarifario.categorias', []));
    $catsGar = $catsGar->only(['preventivo', 'instalacion', 'correctivo', 'refrigeracion_comercial']);
    if ($tipoVal === 'preventivo_recurrente') { $catsGar = $catsGar->only(['preventivo']); }
    elseif ($tipoVal === 'instalacion') { $catsGar = $catsGar->only(['instalacion']); }
    elseif ($tipoVal === 'correctivo') { $catsGar = $catsGar->only(['correctivo']); }
    $nombreCat = ['preventivo' => 'Mantenimiento preventivo', 'instalacion' => 'Instalación', 'correctivo' => 'Reparación correctiva', 'refrigeracion_comercial' => 'Refrigeración comercial'];
@endphp
@if($catsGar->count() || filled($c->garantias))
<div class="seccion">Garantías</div>
@if($catsGar->count())
<p>Los plazos de garantía de la mano de obra corren desde la fecha de cierre de la orden de trabajo de cada servicio, no desde la firma de este contrato.</p>
<table class="tabla">
    <tr><th>Servicio</th><th>Plazo</th><th>Cubre</th><th>No cubre</th></tr>
    @foreach($catsGar as $k => $g)
    <tr><td>{{ $nombreCat[$k] ?? ucfirst(str_replace('_', ' ', (string) $k)) }}</td><td>{{ $g['dias'] ?? '—' }} días</td><td>{{ $g['cubre'] ?? '' }}</td><td>{{ $g['no_cubre'] ?? '' }}</td></tr>
    @endforeach
</table>
<p>Para solicitar la garantía, el Cliente debe avisar a la Empresa indicando el número del certificado y de la orden de trabajo. La garantía cubre los defectos atribuibles a la ejecución del trabajo. Si un tercero interviene el equipo, la garantía no se anula en su totalidad, pero los daños que se deriven de esa intervención no están cubiertos. Aplican las Condiciones Generales de Garantía (CT-GAR-001), que no limitan los derechos irrenunciables del consumidor conforme a la legislación hondureña.</p>
@endif
@if(filled($c->garantias))
<p>{!! nl2br(e($c->garantias)) !!}</p>
@endif
@endif

@if(is_array($c->sla) && count($c->sla))
<div class="seccion">Nivel de servicio</div>
<table class="tabla">
@foreach($c->sla as $k => $v)
    @if(is_scalar($v))
    <tr><td>{{ ucfirst(str_replace('_', ' ', (string) $k)) }}</td><td>{{ $v }}</td></tr>
    @endif
@endforeach
</table>
@endif

@php($cotO = \App\Models\Cotizacion::where('contrato_id', $c->id)->first())
@php($ra = $cotO ? \App\Services\ResumenAnual::resumen($cotO) : null)
@if($ra)
<div class="seccion">Alcance anual</div>
<p>{{ $ra['texto'] }}. Valor anual: L {{ number_format($ra['subtotal'], 2) }} más ISV (L {{ number_format($ra['isv'], 2) }}), total L {{ number_format($ra['total'], 2) }}.</p>
<div class="seccion">Compromiso anual y descuento</div>
<p>El descuento del plan se otorga a cambio del compromiso de contratar las {{ $ra['visitas'] }} visitas del año. Si el Cliente cancela antes de completarlas, pagará la diferencia entre la tarifa regular y la tarifa con descuento de las visitas ya realizadas o programadas.</p>
@endif

@if($c->equipos->count())
<div class="seccion">Equipos cubiertos</div>
<table class="tabla">
    <tr><th>Código</th><th>Tipo</th><th>Modelo</th></tr>
    @foreach($c->equipos as $e)
    <tr><td>{{ $e->codigo }}</td><td>{{ $e->tipo?->nombre }}</td><td>{{ $e->modelo }}</td></tr>
    @endforeach
</table>
@endif

@if((float) $c->cuota_monto > 0)
<div class="seccion">Valor</div>
<p>Cuota: L {{ number_format((float) $c->cuota_monto, 2) }}@if($c->periodicidad) · {{ $c->periodicidad }}@endif</p>
@endif

<div class="seccion">Cláusulas generales</div>
@foreach(config('contrato.clausulas', []) as $i => $cl)
<p><strong>{{ $i + 1 }}. {{ $cl['titulo'] }}.</strong> {{ $cl['texto'] }}</p>
@endforeach
