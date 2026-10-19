<x-filament-panels::page>
    @assets
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    @endassets

    <div
        x-data="{
            init() {
                this.$nextTick(() => {
                    this.$root.querySelectorAll('[data-columna]').forEach((col) => {
                        Sortable.create(col, {
                            group: 'ot',
                            animation: 150,
                            onEnd: async (e) => {
                                if (e.from === e.to) return;
                                await this.$wire.moverOt(Number(e.item.dataset.id), e.to.dataset.columna);
                                this.$wire.$refresh();
                            },
                        });
                    });
                });
            },
        }"
        style="display:flex; gap:12px; overflow-x:auto; align-items:flex-start; padding-bottom:12px;"
    >
        @foreach ($this->getColumnas() as $columna)
            <div wire:key="col-{{ $columna['estado']->value }}" style="min-width:260px; width:260px; flex:none; background:rgba(128,128,128,.12); border-radius:10px; padding:8px;">
                <div style="font-weight:600; padding:4px 6px 8px;">
                    {{ $columna['estado']->getLabel() }}
                    <span style="opacity:.6;">({{ $columna['ordenes']->count() }})</span>
                </div>

                <div data-columna="{{ $columna['estado']->value }}" style="min-height:80px; display:flex; flex-direction:column; gap:8px;">
                    @foreach ($columna['ordenes'] as $ot)
                        <div wire:key="ot-{{ $ot->id }}" data-id="{{ $ot->id }}"
                             style="background:rgba(128,128,128,.2); border:1px solid rgba(128,128,128,.3); border-radius:8px; padding:8px; cursor:grab; font-size:13px;">
                            <div style="font-weight:600;">{{ $ot->numero }}</div>
                            <div>{{ $ot->cliente?->razon_social }}</div>
                            <div style="opacity:.7;">{{ $ot->sucursal?->nombre }}</div>
                            <div style="opacity:.7; margin-top:4px;">
                                {{ $ot->tecnico?->user?->name ?? 'Sin técnico' }}
                                @if ($ot->fecha_programada) · {{ $ot->fecha_programada->format('d/m') }} @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
