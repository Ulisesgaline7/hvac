<x-filament-panels::page>
    @assets
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/locales/es.global.min.js"></script>
    @endassets

    <div style="display:flex;gap:16px;flex-wrap:wrap;align-items:flex-end">
        <div style="min-width:220px">
            <label class="text-sm font-medium">Técnico</label>
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="tecnico">
                    <option value="">Todos</option>
                    @foreach($this->tecnicos() as $id => $nombre)
                        <option value="{{ $id }}">{{ $nombre }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>
        <div style="min-width:220px">
            <label class="text-sm font-medium">Estado</label>
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="estado">
                    <option value="">Todos</option>
                    @foreach($this->estados() as $valor => $etiqueta)
                        <option value="{{ $valor }}">{{ $etiqueta }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>
        <div style="display:flex;gap:14px;flex-wrap:wrap;font-size:13px">
            @foreach($this->leyenda() as $l)
                <span><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:{{ $l['color'] }};margin-right:5px"></span>{{ $l['label'] }}</span>
            @endforeach
        </div>
    </div>

    <div wire:ignore style="background:#fff;color:#111;padding:14px;border-radius:12px">
        <div id="calendario-ot"></div>
    </div>

    @script
    <script>
        const cal = new FullCalendar.Calendar(document.getElementById('calendario-ot'), {
            locale: 'es',
            firstDay: 0,
            initialView: 'dayGridMonth',
            height: 'auto',
            navLinks: true,
            nowIndicator: true,
            dayMaxEvents: true,
            slotMinTime: '06:00:00',
            slotMaxTime: '20:00:00',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
            },
            events: (info, ok, fail) => $wire.eventos(info.startStr, info.endStr).then(ok).catch(fail),
            eventDidMount: (i) => {
                const p = i.event.extendedProps;
                i.el.title = [i.event.title, p.estado, p.sucursal, p.tecnico].filter(Boolean).join('\n');
            }
        });
        cal.render();
        $wire.$watch('tecnico', () => cal.refetchEvents());
        $wire.$watch('estado', () => cal.refetchEvents());
    </script>
    @endscript
</x-filament-panels::page>
