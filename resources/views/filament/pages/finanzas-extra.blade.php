@php
    $l = fn ($v) => 'L ' . number_format((float) $v, 2);
    $grid = 'display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:1rem;';
    $c = 'padding:.4rem .75rem;border-bottom:1px solid rgba(128,128,128,.25);';
    $r = $c . 'text-align:right;';
    $in = 'border:1px solid rgba(128,128,128,.4);border-radius:.4rem;padding:.3rem .5rem;background:transparent;width:100%;';
    $err = 'color:#dc2626;font-size:.8rem;';
@endphp

<x-filament::section heading="Utilidad estimada del periodo">
    <div style="font-size:1.5rem;font-weight:600;">{{ $l($x['utilidad']) }}</div>
    <div>Facturado sin ISV {{ $l($x['facturado_sin_isv']) }} − gastos sin ISV {{ $l($x['gastos']['total']['subtotal']) }} − pagos a técnicos {{ $l($x['tecnicos']['total_pagos']) }}. Es una guía: no incluye sueldos fijos, alquiler ni otros costos.</div>
</x-filament::section>

<div style="{{ $grid }}">
    <x-filament::section heading="Impuestos: ISV a declarar (guía)">
        <table style="width:100%;">
            <tr><td style="{{ $c }}">ISV de ventas (facturado)</td><td style="{{ $r }}">{{ $l($x['isv']['ventas']) }}</td></tr>
            <tr><td style="{{ $c }}">ISV de compras (crédito)</td><td style="{{ $r }}">− {{ $l($x['isv']['compras']) }}</td></tr>
            <tr><td style="{{ $c }}"><strong>ISV neto a pagar</strong></td><td style="{{ $r }}"><strong>{{ $l($x['isv']['neto']) }}</strong></td></tr>
            <tr><td style="{{ $c }}">Del ISV de ventas, ya cobrado</td><td style="{{ $r }}">{{ $l($x['isv']['ventas_cobrado']) }}</td></tr>
        </table>
        <div style="font-size:.8rem;margin-top:.5rem;">Solo cuenta como crédito el ISV de gastos con factura de proveedor. Documentos aún «de prueba» y sin CAI: validar con el contador.</div>
    </x-filament::section>

    <x-filament::section heading="Cobros a clientes por método">
        <table style="width:100%;">
            @forelse ($x['cobros']['por_metodo'] as $k => $v)
                <tr><td style="{{ $c }}">{{ $k }}</td><td style="{{ $r }}">{{ $l($v) }}</td></tr>
            @empty
                <tr><td style="{{ $c }}">Sin cobros en el periodo</td></tr>
            @endforelse
        </table>
    </x-filament::section>
</div>

<div style="{{ $grid }}">
    <x-filament::section heading="Últimos cobros">
        <table style="width:100%;">
            @forelse ($x['cobros']['ultimos'] as $p)
                <tr><td style="{{ $c }}">{{ $p['numero'] }} · {{ \Carbon\Carbon::parse($p['fecha'])->format('d/m') }}<br><small>{{ $p['razon_social'] }} · {{ $p['tipo'] }}</small></td><td style="{{ $r }}">{{ $l($p['monto']) }}</td></tr>
            @empty
                <tr><td style="{{ $c }}">Sin cobros</td></tr>
            @endforelse
        </table>
    </x-filament::section>

    <x-filament::section heading="Facturas por cobrar (más atrasadas)">
        <table style="width:100%;">
            @forelse ($x['cobros']['abiertas'] as $f)
                <tr><td style="{{ $c }}">{{ $f['numero'] }} · vence {{ $f['vence'] }}<br><small>{{ $f['cliente'] }}{{ $f['atraso'] ? ' · ' . $f['atraso'] . ' días de atraso' : '' }}</small></td><td style="{{ $r }}">{{ $l($f['saldo']) }}</td></tr>
            @empty
                <tr><td style="{{ $c }}">Sin facturas abiertas</td></tr>
            @endforelse
        </table>
    </x-filament::section>
</div>

<div style="{{ $grid }}">
    <x-filament::section heading="Pagos a técnicos (por estado)">
        <table style="width:100%;">
            @forelse ($x['tecnicos']['pagos'] as $t)
                <tr><td style="{{ $c }}">{{ $t['estado'] }} ({{ $t['n'] }})</td><td style="{{ $r }}">{{ $l($t['monto']) }}</td></tr>
            @empty
                <tr><td style="{{ $c }}">Sin pagos generados</td></tr>
            @endforelse
        </table>
    </x-filament::section>

    <x-filament::section heading="Liquidaciones de técnicos">
        <table style="width:100%;">
            @forelse ($x['tecnicos']['liquidaciones'] as $t)
                <tr><td style="{{ $c }}">{{ $t['estado'] }} ({{ $t['n'] }})<br><small>bruto {{ $l($t['bruto']) }} · retención {{ $l($t['retencion']) }}</small></td><td style="{{ $r }}">neto {{ $l($t['neto']) }}</td></tr>
            @empty
                <tr><td style="{{ $c }}">Sin liquidaciones en el periodo</td></tr>
            @endforelse
        </table>
    </x-filament::section>
</div>

<div style="{{ $grid }}">
    <x-filament::section heading="Gastos por categoría">
        <table style="width:100%;">
            @forelse ($x['gastos']['por_categoria'] as $g)
                <tr><td style="{{ $c }}">{{ $g['categoria'] }} ({{ $g['n'] }})<br><small>ISV {{ $l($g['isv']) }}</small></td><td style="{{ $r }}">{{ $l($g['total']) }}</td></tr>
            @empty
                <tr><td style="{{ $c }}">Sin gastos en el periodo</td></tr>
            @endforelse
            <tr><td style="{{ $c }}"><strong>Total</strong></td><td style="{{ $r }}"><strong>{{ $l($x['gastos']['total']['total']) }}</strong></td></tr>
        </table>
    </x-filament::section>

    <x-filament::section heading="Registrar gasto">
        <form wire:submit="guardarGasto" style="display:grid;gap:.5rem;">
            <label>Fecha <input type="date" wire:model="g.fecha" style="{{ $in }}"></label>
            @error('g.fecha') <span style="{{ $err }}">{{ $message }}</span> @enderror
            <label>Proveedor registrado
                <select wire:model="g.proveedor_id" style="{{ $in }}">
                    <option value="">— otro (escribirlo abajo) —</option>
                    @foreach ($x['proveedores'] as $pid => $pn) <option value="{{ $pid }}">{{ $pn }}</option> @endforeach
                </select>
            </label>
            <label>Proveedor (texto libre, si no está en la lista) <input type="text" wire:model="g.proveedor" style="{{ $in }}"></label>
            @error('g.proveedor') <span style="{{ $err }}">{{ $message }}</span> @enderror
            <label>Concepto <input type="text" wire:model="g.concepto" style="{{ $in }}"></label>
            @error('g.concepto') <span style="{{ $err }}">{{ $message }}</span> @enderror
            <label>Categoría
                <select wire:model="g.categoria" style="{{ $in }}">
                    @foreach ($x['categorias'] as $k => $n) <option value="{{ $k }}">{{ $n }}</option> @endforeach
                </select>
            </label>
            <label>Monto sin ISV (L) <input type="number" step="0.01" wire:model="g.subtotal" style="{{ $in }}"></label>
            @error('g.subtotal') <span style="{{ $err }}">{{ $message }}</span> @enderror
            <label>ISV pagado (L, vacío si no hay factura con ISV) <input type="number" step="0.01" wire:model="g.isv" style="{{ $in }}"></label>
            @error('g.isv') <span style="{{ $err }}">{{ $message }}</span> @enderror
            <label>No. de factura del proveedor <input type="text" wire:model="g.factura" style="{{ $in }}"></label>
            <label>Método de pago
                <select wire:model="g.metodo" style="{{ $in }}">
                    <option value="">—</option><option value="efectivo">Efectivo</option><option value="transferencia">Transferencia</option><option value="tarjeta">Tarjeta</option><option value="cheque">Cheque</option>
                </select>
            </label>
            <x-filament::button type="submit">Guardar gasto</x-filament::button>
        </form>
    </x-filament::section>
</div>

<x-filament::section heading="Gastos del periodo (últimos 30)">
    <table style="width:100%;">
        @forelse ($x['gastos']['lista'] as $g)
            <tr>
                <td style="{{ $c }}">{{ \Carbon\Carbon::parse($g['fecha'])->format('d/m/Y') }} · {{ $g['proveedor'] }}<br><small>{{ $g['concepto'] }} · {{ $g['cat'] }}{{ $g['factura_proveedor'] ? ' · fact. ' . $g['factura_proveedor'] : '' }}</small></td>
                <td style="{{ $r }}">{{ $l($g['total']) }}</td>
                <td style="{{ $r }}"><button type="button" wire:click="borrarGasto({{ $g['id'] }})" wire:confirm="¿Borrar este gasto?" style="color:#dc2626;">Borrar</button></td>
            </tr>
        @empty
            <tr><td style="{{ $c }}">Sin gastos registrados</td></tr>
        @endforelse
    </table>
</x-filament::section>

<div style="{{ $grid }}">
    <x-filament::section heading="Inventario (valor de existencias)">
        <div style="font-size:1.5rem;font-weight:600;">{{ $l($x['inv']['valor']['total']) }}</div>
        <div>{{ $x['inv']['valor']['insumos'] }} insumos activos · stock × costo. Es un activo, no un gasto.</div>
        <table style="width:100%;margin-top:.5rem;">
            <tr><td style="{{ $c }}">Entradas del periodo (a costo)</td><td style="{{ $r }}">{{ $l($x['inv']['entradas']) }}</td></tr>
            <tr><td style="{{ $c }}">Consumo del periodo (salidas)</td><td style="{{ $r }}">{{ $l($x['inv']['consumo']) }}</td></tr>
        </table>
        <div style="font-size:.8rem;margin-top:.5rem;">No se suma a la utilidad para no contar dos veces: la compra ya está en Gastos.</div>
    </x-filament::section>

    <x-filament::section heading="Insumos bajo el mínimo">
        <table style="width:100%;">
            @forelse ($x['inv']['bajo_minimo'] as $i)
                <tr><td style="{{ $c }}">{{ $i['codigo'] }} · {{ $i['nombre'] }}</td><td style="{{ $r }}">{{ number_format((float) $i['stock'], 2) }} / {{ number_format((float) $i['stock_minimo'], 2) }} {{ $i['unidad'] }}</td></tr>
            @empty
                <tr><td style="{{ $c }}">Todo sobre el mínimo</td></tr>
            @endforelse
        </table>
    </x-filament::section>

    <x-filament::section heading="Compras por proveedor (periodo)">
        <table style="width:100%;">
            @forelse ($x['inv']['por_proveedor'] as $p)
                <tr><td style="{{ $c }}">{{ $p['proveedor'] }} ({{ $p['n'] }})</td><td style="{{ $r }}">{{ $l($p['total']) }}</td></tr>
            @empty
                <tr><td style="{{ $c }}">Sin compras en el periodo</td></tr>
            @endforelse
        </table>
    </x-filament::section>
</div>
