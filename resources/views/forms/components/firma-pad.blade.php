<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            state: $wire.$entangle('{{ $getStatePath() }}'),
            drawing: false,
            ctx: null,
            prepare() {
                const c = this.$refs.canvas;
                const w = c.clientWidth || 600;
                if (this.ctx && c.width === w) return;
                c.width = w;
                c.height = 160;
                this.ctx = c.getContext('2d');
                this.ctx.lineWidth = 2;
                this.ctx.lineCap = 'round';
                this.ctx.lineJoin = 'round';
                this.ctx.strokeStyle = '#111827';
            },
            pos(e) {
                const b = this.$refs.canvas.getBoundingClientRect();
                return {
                    x: (e.clientX - b.left) * (this.$refs.canvas.width / b.width),
                    y: (e.clientY - b.top) * (this.$refs.canvas.height / b.height),
                };
            },
            start(e) {
                e.preventDefault();
                this.prepare();
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
                this.prepare();
                this.ctx.clearRect(0, 0, this.$refs.canvas.width, this.$refs.canvas.height);
                this.state = null;
            },
        }"
        x-init="$nextTick(() => prepare())"
    >
        <canvas
            x-ref="canvas"
            style="display:block; width:100%; min-width:260px; height:160px; background:#fff; border:2px dashed #9ca3af; border-radius:8px; touch-action:none; cursor:crosshair;"
            @pointerdown="start($event)"
            @pointermove="move($event)"
            @pointerup="end()"
            @pointercancel="end()"
        ></canvas>
        <button type="button" @click="clear()" style="margin-top:4px; font-size:13px; text-decoration:underline;">Borrar firma</button>
    </div>
</x-dynamic-component>
