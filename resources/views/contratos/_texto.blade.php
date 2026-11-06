@php
    $tipo = $c->tipo instanceof \Filament\Support\Contracts\HasLabel ? $c->tipo->getLabel() : (is_object($c->tipo) ? $c->tipo->value : $c->tipo);
    $f = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('d/m/Y') : '—';
    $empresa = \App\Support\DocumentoMarca::todo()['empresa_nombre'] ?? 'La Empresa';
@endphp
<div class="seccion">Partes</div>
<p>Entre <strong>{{ $empresa }}</strong> («la Empresa») y <strong>{{ $c->cliente->razon_social ?? '' }}</strong> («el Cliente»), se acuerda el presente contrato de servicio ({{ $tipo }}).</p>

<div class="seccion">Vigencia</div>
<p>Del {{ $f($c->vigencia_inicio) }} al {{ $f($c->vigencia_fin) }}.</p>

@if(filled($c->servicios_incluidos))
<div class="seccion">Servicios incluidos</div>
<p>{!! nl2br(e($c->servicios_incluidos)) !!}</p>
@endif

@if(filled($c->exclusiones))
<div class="seccion">Exclusiones</div>
<p>{!! nl2br(e($c->exclusiones)) !!}</p>
@endif

@if(filled($c->garantias))
<div class="seccion">Garantías</div>
<p>{!! nl2br(e($c->garantias)) !!}</p>
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
