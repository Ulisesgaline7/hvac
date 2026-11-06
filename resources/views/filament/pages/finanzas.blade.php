@php
    $d = $this->datos();
    $l = fn ($v) => 'L ' . number_format((float) $v, 2);
    $vencido = collect($d['por_cobrar']['antiguedad'])->except('Por vencer')->sum();
    $grid = 'display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;';
    $celda = 'padding:.4rem .75rem;border-bottom:1px solid rgba(128,128,128,.25);';
@endphp

<x-filament-panels::page>
    <div style="display:flex;gap:1rem;flex-wrap:wrap;align-items:end;">
        <label>Desde<br><input type="date" wire:model.live="desde" style="border:1px solid rgba(128,128,128,.4);border-radius:.4rem;padding:.3rem .5rem;background:transparent;"></label>
        <label>Hasta<br><input type="date" wire:model.live="hasta" style="border:1px solid rgba(128,128,128,.4);border-radius:.4rem;padding:.3rem .5rem;background:transparent;"></label>
    </div>

    <div style="{{ $grid }}">
        <x-filament::section heading="Facturado (sin ISV)">
            <div style="font-size:1.5rem;font-weight:600;">{{ $l($d['facturado']['subtotal']) }}</div>
            <div>{{ $d['facturado']['n'] }} facturas · total con ISV {{ $l($d['facturado']['total']) }}</div>
        </x-filament::section>
        <x-filament::section heading="Cobrado">
            <div style="font-size:1.5rem;font-weight:600;">{{ $l($d['cobrado']['total']) }}</div>
            <div>Pagos {{ $l($d['cobrado']['pagos']) }} · anticipos {{ $l($d['cobrado']['anticipos']) }}</div>
        </x-filament::section>
        <x-filament::section heading="Por cobrar">
            <div style="font-size:1.5rem;font-weight:600;">{{ $l($d['por_cobrar']['total']) }}</div>
            <div>{{ $d['por_cobrar']['facturas'] }} facturas abiertas</div>
        </x-filament::section>
        <x-filament::section heading="Vencido">
            <div style="font-size:1.5rem;font-weight:600;color:{{ $vencido > 0 ? '#dc2626' : 'inherit' }};">{{ $l($vencido) }}</div>
            <div>Saldo con fecha de vencimiento pasada</div>
        </x-filament::section>
    </div>

    <div style="{{ $grid }}">
        <x-filament::section heading="Antigüedad de saldos">
            <table style="width:100%;">
                @foreach ($d['por_cobrar']['antiguedad'] as $k => $v)
                    <tr><td style="{{ $celda }}">{{ $k }}</td><td style="{{ $celda }}text-align:right;">{{ $l($v) }}</td></tr>
                @endforeach
            </table>
        </x-filament::section>
        <x-filament::section heading="Clientes con más saldo">
            <table style="width:100%;">
                @forelse ($d['por_cobrar']['top_clientes'] as $k => $v)
                    <tr><td style="{{ $celda }}">{{ $k }}</td><td style="{{ $celda }}text-align:right;">{{ $l($v) }}</td></tr>
                @empty
                    <tr><td style="{{ $celda }}">Sin saldos pendientes</td></tr>
                @endforelse
            </table>
        </x-filament::section>
        <x-filament::section heading="Pagos a técnicos">
            <table style="width:100%;">
                @forelse ($d['tecnicos'] as $k => $t)
                    <tr><td style="{{ $celda }}">{{ $k }} ({{ $t['n'] }})</td><td style="{{ $celda }}text-align:right;">{{ $l($t['monto']) }}</td></tr>
                @empty
                    <tr><td style="{{ $celda }}">Sin pagos generados</td></tr>
                @endforelse
            </table>
        </x-filament::section>
    </div>

    <x-filament::section heading="Otros datos del periodo">
        <div>ISV facturado: {{ $l($d['facturado']['isv']) }} · de contratos: {{ $l($d['facturado']['contrato']) }}</div>
        <div>Anticipos aún sin factura (todo el tiempo): {{ $l($d['anticipos_sin_factura']) }}</div>
    </x-filament::section>
    @include('filament.pages.finanzas-extra', ['x' => $this->extra()])
</x-filament-panels::page>
