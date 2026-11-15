<x-filament-panels::page>
    <x-filament::section>
        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:12px;flex-wrap:wrap">
            <x-filament::button wire:click="anterior" color="gray" size="sm">‹ Anterior</x-filament::button>
            <strong>{{ $this->titulo() }}</strong>
            <div style="display:flex;gap:8px">
                <x-filament::button wire:click="hoy" color="gray" size="sm">Hoy</x-filament::button>
                <x-filament::button wire:click="siguiente" color="gray" size="sm">Siguiente ›</x-filament::button>
            </div>
        </div>

        <div style="overflow-x:auto">
            <div style="display:grid;grid-template-columns:repeat(7,minmax(110px,1fr));gap:4px;min-width:770px">
                @foreach (['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'] as $n)
                    <div style="font-weight:600;text-align:center;padding:4px">{{ $n }}</div>
                @endforeach
                @foreach ($this->semanas() as $semana)
                    @foreach ($semana as $celda)
                        @if ($celda)
                            <div style="min-height:86px;padding:4px;border:1px solid rgba(128,128,128,.3);border-radius:6px;{{ $celda['hoy'] ? 'outline:2px solid #2563eb;' : '' }}">
                                <div style="font-size:12px;opacity:.7">{{ $celda['dia'] }}</div>
                                @foreach ($celda['ev'] as $e)
                                    <div style="font-size:11px;margin-top:2px;padding:2px 4px;border-radius:4px;color:#fff;background:{{ $e[1] }}">{{ $e[0] }}</div>
                                @endforeach
                            </div>
                        @else
                            <div></div>
                        @endif
                    @endforeach
                @endforeach
            </div>
        </div>

        <div style="display:flex;gap:14px;margin-top:12px;font-size:12px;flex-wrap:wrap">
            <span><span style="color:#2563eb">■</span> Visita programada</span>
            <span><span style="color:#16a34a">■</span> Visita realizada</span>
            <span><span style="color:#d97706">■</span> Vence garantía de servicio</span>
            <span><span style="color:#dc2626">■</span> Vence garantía de fábrica</span>
        </div>
    </x-filament::section>

    <x-filament::section heading="Próximas visitas">
        @forelse ($this->proximas() as $p)
            <div style="padding:4px 0">{{ $p[0] }} · {{ $p[1] }} · {{ $p[2] }}</div>
        @empty
            <p>No hay visitas programadas.</p>
        @endforelse
    </x-filament::section>
</x-filament-panels::page>
