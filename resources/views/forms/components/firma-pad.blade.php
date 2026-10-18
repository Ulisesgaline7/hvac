<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        wire:ignore
        x-data="{
            state: $wire.$entangle('{{ $getStatePath() }}'),
            drawing: false,
            ctx: null,
            init() {
                const c = this.$refs.canvas;
                const r = window.devicePixelRatio || 1;
                c.width = c.offsetWidth * r;
                c.height = 160 * r;
                this.ctx = c.getContext('2d');
                this.ctx.scale(r, r);
                this.ctx.lineWidth = 2;
                this.ctx.lineCap = 'round';
                this.ctx.lineJoin = 'round';
                this.ctx.strokeStyle = '#111827';
            },
            pos(e) {
                const b = this.$refs.canvas.getBoundingClientRect();
                return { x: e.clientX - b.left, y: e.clientY - b.top };
            },
            start(e) {
                e.preventDefault();
                this.$refs.canvas.setPointerCapture(e.pointerId);
                this.drawing = true;
                const p = this.pos(e);
                this.ctx.beginPath();
                this.ctx.moveTo(p.x, p.y);
            },
            move(e) {
                if (! this.drawing) return;
                e.preventDefault();
                const p = this.pos(e);
                this.ctx.lineTo(p.x, p.y);
                this.ctx.stroke();
            },
            end() {
                if (! this.drawing) return;
                this.drawing = false;
                this.state = this.$refs.canvas.toDataURL('image/png');
            },
            clear() {
                this.ctx.clearRect(0, 0, this.$refs.canvas.width, this.$refs.canvas.height);
                this.state = null;
            },
        }"
    >
        <canvas
            x-ref="canvas"
            class="w-full rounded-lg border border-gray-300 bg-white dark:border-gray-600"
            style="height:160px; touch-action:none; cursor:crosshair;"
            @pointerdown="start($event)"
            @pointermove="move($event)"
            @pointerup="end()"
            @pointerleave="end()"
            @pointercancel="end()"
        ></canvas>
        <button type="button" @click="clear()" class="mt-1 text-sm text-primary-600 underline">Borrar firma</button>
        <p class="text-xs text-gray-500">Si ya hay una firma guardada y no dibujas otra, se conserva la actual.</p>
    </div>
</x-dynamic-component>
