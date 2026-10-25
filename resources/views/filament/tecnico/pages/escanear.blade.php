<x-filament-panels::page>
    <div
        x-data="{
            error: null,
            activo: false,
            lector: null,
            async cargar() {
                if (window.Html5Qrcode) return;
                await new Promise((ok, fallo) => {
                    const s = document.createElement('script');
                    s.src = 'https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js';
                    s.onload = ok; s.onerror = () => fallo(new Error('No se pudo cargar el lector. Revise la conexión.'));
                    document.head.appendChild(s);
                });
            },
            destino(texto) {
                try {
                    const u = new URL(texto);
                    if (u.host === window.location.host && /^\/e\/[A-Za-z0-9]{32}$/.test(u.pathname)) return u.pathname;
                } catch (e) {}
                return null;
            },
            async iniciar() {
                this.error = null;
                try {
                    await this.cargar();
                    this.lector = new Html5Qrcode('lector-qr');
                    await this.lector.start(
                        { facingMode: 'environment' },
                        { fps: 10, qrbox: { width: 240, height: 240 } },
                        async (texto) => {
                            const ruta = this.destino(texto);
                            if (!ruta) { this.error = 'Ese código no es de un equipo de este sistema.'; return; }
                            await this.lector.stop();
                            window.location.href = ruta;
                        },
                        () => {}
                    );
                    this.activo = true;
                } catch (e) {
                    this.error = e && e.message ? e.message : 'No se pudo abrir la cámara. Permita el acceso a la cámara en el navegador.';
                }
            },
            async detener() {
                if (this.lector) { try { await this.lector.stop(); } catch (e) {} }
                this.activo = false;
            },
        }"
        x-on:beforeunload.window="detener()"
    >
        <div wire:ignore id="lector-qr" style="width:100%;max-width:420px;margin:0 auto;"></div>

        <p x-show="error" x-text="error" style="color:#b91c1c;text-align:center;margin-top:.75rem;"></p>

        <div style="text-align:center;margin-top:1rem;">
            <x-filament::button x-show="!activo" x-on:click="iniciar()" icon="heroicon-o-camera" size="lg">
                Abrir cámara
            </x-filament::button>
            <x-filament::button x-show="activo" x-on:click="detener()" color="gray" size="lg">
                Detener
            </x-filament::button>
        </div>

        <p style="text-align:center;margin-top:1rem;font-size:.9rem;color:#6b7280;">
            Apunte al QR pegado en el equipo. También sirve la cámara normal del teléfono.
        </p>
    </div>
</x-filament-panels::page>
