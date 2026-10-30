<x-filament-panels::page>
    @assets
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
    @endassets

    <div style="display:flex;gap:18px;flex-wrap:wrap;align-items:center">
        <div style="min-width:260px">
            <x-filament::input.wrapper>
                <x-filament::input type="search" id="buscar-sucursal" placeholder="Buscar cliente o sucursal…" />
            </x-filament::input.wrapper>
        </div>
        <span style="font-size:13px">
            <span style="display:inline-block;width:11px;height:11px;border-radius:50%;background:#DC2626;margin-right:5px"></span>Con órdenes abiertas
            <span style="display:inline-block;width:11px;height:11px;border-radius:50%;background:#087BFF;margin:0 5px 0 16px"></span>Sin órdenes abiertas
        </span>
    </div>

    <div wire:ignore id="mapa-sucursales" style="height:65vh;min-height:420px;border-radius:12px;isolation:isolate;z-index:0"></div>

    @if(count($this->sinUbicacion()))
        <x-filament::section heading="Sucursales sin ubicación" description="Abre cada una y llena latitud y longitud (en Google Maps: clic derecho sobre el lugar y copia las coordenadas).">
            <ul style="margin:0;padding-left:18px">
                @foreach($this->sinUbicacion() as $s)
                    <li><a href="{{ $s['url'] }}" style="text-decoration:underline">{{ $s['cliente'] }} — {{ $s['nombre'] }}</a></li>
                @endforeach
            </ul>
        </x-filament::section>
    @endif

    @script
    <script>
        (async () => {
            const esc = (t) => String(t ?? '').replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
            const map = L.map('mapa-sucursales').setView([14.6, -86.8], 7);
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; Colaboradores de OpenStreetMap'
            }).addTo(map);
            const capa = L.layerGroup().addTo(map);
            const puntos = await $wire.puntos();

            const pintar = (q) => {
                capa.clearLayers();
                const b = [];
                puntos.filter((p) => !q || (p.cliente + ' ' + p.nombre).toLowerCase().includes(q)).forEach((p) => {
                    const html = '<strong>' + esc(p.cliente) + '</strong><br>' + esc(p.nombre)
                        + (p.direccion ? '<br>' + esc(p.direccion) : '')
                        + '<br>Órdenes abiertas: <strong>' + p.abiertas + '</strong>'
                        + '<br><a href="' + p.url + '">Editar sucursal</a> · '
                        + '<a href="https://www.google.com/maps?q=' + p.lat + ',' + p.lng + '" target="_blank" rel="noopener">Abrir en Google Maps</a>';
                    L.circleMarker([p.lat, p.lng], {
                        radius: 10, color: '#fff', weight: 2,
                        fillColor: p.abiertas > 0 ? '#DC2626' : '#087BFF', fillOpacity: 0.95
                    }).bindPopup(html).addTo(capa);
                    b.push([p.lat, p.lng]);
                });
                if (b.length) map.fitBounds(b, {padding: [40, 40], maxZoom: 15});
            };

            pintar('');
            document.getElementById('buscar-sucursal').addEventListener('input', (e) => pintar(e.target.value.trim().toLowerCase()));
        })();
    </script>
    @endscript
</x-filament-panels::page>
