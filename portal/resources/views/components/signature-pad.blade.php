@props([
    'name',                  // hidden input name + Livewire property name
    'width'   => 400,
    'height'  => 120,
])

<div
    class="signature-pad"
    x-data="signaturePad(@js($name), @js($width), @js($height))"
    x-init="init()"
    wire:ignore
>
    <canvas
        x-ref="canvas"
        width="{{ $width }}"
        height="{{ $height }}"
        class="signature-pad__canvas border rounded bg-white"
        style="touch-action: none; max-width: 100%;"
    ></canvas>

    <input
        type="hidden"
        x-model="dataUrl"
        :name="name"
    />

    <div class="flex flex-wrap items-center gap-2 mt-1 text-xs text-base-content/60">
        <button type="button" class="btn btn-xs btn-ghost gap-1" @click="clear()">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/></svg>
            Clear
        </button>
        <span x-show="hasInk" x-cloak>
            <span x-text="byteSize"></span> bytes captured
        </span>
        <span class="text-error" x-show="error" x-cloak x-text="error"></span>
    </div>
</div>

@once
    @push('scripts')
        <script>
            // Tiny canvas + pointer-event drawing pad.
            // Publishes a base64 PNG into the hidden input. Because the
            // surrounding div carries `wire:ignore`, Livewire doesn't
            // touch the canvas — but it still observes the hidden input
            // (which sits *inside* the wire:ignore, so it would be
            // ignored too). To make the value flow back into the
            // Livewire property whose name matches `name`, the hidden
            // input gets `x-model` AND we dispatch a manual `input`
            // event in `commit()` so Livewire's `wire:model.cheap`
            // listener picks it up.
            window.signaturePad = function (name, w, h) {
                return {
                    name, w, h,
                    dataUrl: '',
                    hasInk: false,
                    error: '',
                    ctx: null,
                    drawing: false,
                    last: null,
                    get byteSize() {
                        if (! this.dataUrl) return 0;
                        const b64 = this.dataUrl.split(',')[1] || '';
                        return Math.floor((b64.length * 3) / 4);
                    },
                    init() {
                        const canvas = this.$refs.canvas;
                        this.ctx = canvas.getContext('2d');
                        this.ctx.lineWidth = 2;
                        this.ctx.lineCap = 'round';
                        this.ctx.strokeStyle = '#111';

                        // Listen for a draft-restore from the parent
                        // form. When a saved dataUrl is drawn back
                        // onto our canvas, we update hasInk + dataUrl
                        // so the form's "in-progress" state matches
                        // what's on screen.
                        window.addEventListener('tsr.signature-hydrated', (ev) => {
                            if (! ev || ! ev.detail) return;
                            if (ev.detail.name !== this.name) return;
                            if (ev.detail.dataUrl) {
                                this.hasInk = true;
                                this.dataUrl = ev.detail.dataUrl;
                                this.push();
                            }
                        });

                        const onDown = (e) => {
                            e.preventDefault();
                            this.drawing = true;
                            this.last = this.point(e);
                        };
                        const onMove = (e) => {
                            if (! this.drawing) return;
                            e.preventDefault();
                            const p = this.point(e);
                            this.ctx.beginPath();
                            this.ctx.moveTo(this.last.x, this.last.y);
                            this.ctx.lineTo(p.x, p.y);
                            this.ctx.stroke();
                            this.last = p;
                            this.hasInk = true;
                        };
                        const onUp = () => {
                            if (! this.drawing) return;
                            this.drawing = false;
                            this.commit();
                        };

                        canvas.addEventListener('pointerdown', onDown);
                        canvas.addEventListener('pointermove', onMove);
                        canvas.addEventListener('pointerup', onUp);
                        canvas.addEventListener('pointercancel', onUp);
                        canvas.addEventListener('pointerleave', onUp);
                    },
                    point(e) {
                        const rect = this.$refs.canvas.getBoundingClientRect();
                        return {
                            x: ((e.clientX - rect.left) * this.w) / rect.width,
                            y: ((e.clientY - rect.top)  * this.h) / rect.height,
                        };
                    },
                    clear() {
                        this.ctx.clearRect(0, 0, this.w, this.h);
                        this.hasInk = false;
                        this.dataUrl = '';
                        this.commit();
                    },
                    commit() {
                        try {
                            this.dataUrl = this.$refs.canvas.toDataURL('image/png');
                            this.push();
                            // Notify the parent tsrForm so it can
                            // schedule a draft autosave. Custom event
                            // bubbles to window so we don't need a
                            // direct reference to the parent component.
                            window.dispatchEvent(new CustomEvent('tsr.signature-committed', {
                                detail: { name: this.name, dataUrl: this.dataUrl }
                            }));
                        } catch (e) {
                            this.error = 'Could not capture signature: ' + e.message;
                        }
                    },
                    push() {
                        // Find the Livewire component root that
                        // *contains* this pad and call set() directly.
                        // This is the most reliable bridge because the
                        // pad sits inside wire:ignore, which blocks
                        // Livewire's normal DOM-driven syncing.
                        const root = this.$root.closest('[wire\\:id]');
                        if (! root) return;
                        const id = root.getAttribute('wire:id');
                        if (! window.Livewire || ! id) return;
                        const comp = window.Livewire.find(id);
                        if (comp) {
                            comp.set(this.name, this.dataUrl || '');
                        }
                    },
                };
            };
        </script>
    @endpush
@endonce
