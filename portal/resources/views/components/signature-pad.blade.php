@props([
    'name',                  // hidden input name + Livewire property name
    'width'   => 500,
    'height'  => 200,
])

@php
    $inputId = 'sig-' . str_replace(['[',']'], '-', $name);
@endphp

<div
    class="signature-pad group"
    x-data="signaturePad(@js($name), @js($width), @js($height))"
    x-init="init()"
    wire:ignore
>
    <div
        class="relative rounded-xl overflow-hidden transition-shadow duration-200"
        :class="hasInk
            ? 'ring-2 ring-success/40 shadow-sm'
            : 'ring-1 ring-base-300 hover:ring-base-content/20 shadow-inner'"
    >
        {{-- Signature guide line + placeholder --}}
        <div
            class="absolute bottom-8 left-4 right-4 pointer-events-none select-none"
            x-show="!hasInk"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        >
            <div class="border-b-2 border-dashed border-base-content/15"></div>
            <p class="text-center text-[11px] text-base-content/25 mt-1.5 tracking-wide uppercase">
                Sign here
            </p>
        </div>

        {{-- Pen icon hint --}}
        <div
            class="absolute top-2.5 right-2.5 pointer-events-none transition-opacity duration-200"
            :class="hasInk ? 'opacity-0' : 'opacity-30 group-hover:opacity-50'"
        >
            <svg class="w-5 h-5 text-base-content" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
            </svg>
        </div>

        <canvas
            x-ref="canvas"
            width="{{ $width }}"
            height="{{ $height }}"
            class="signature-pad__canvas block w-full cursor-crosshair"
            style="touch-action: none; background: white;"
        ></canvas>

        <input
            type="hidden"
            x-model="dataUrl"
            :name="name"
        />
    </div>

    {{-- Controls row --}}
    <div class="flex items-center justify-between mt-2 px-0.5">
        <div class="flex items-center gap-1.5">
            {{-- Undo --}}
            <button
                type="button"
                class="btn btn-xs btn-ghost gap-1 text-base-content/50 hover:text-base-content"
                :class="strokes.length > 0 ? 'visible' : 'invisible'"
                @click="undo()"
                title="Undo last stroke"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 10h10a5 5 0 015 5v2M3 10l4 4M3 10l4-4" />
                </svg>
                Undo
            </button>

            {{-- Clear --}}
            <button
                type="button"
                class="btn btn-xs btn-ghost gap-1 text-base-content/50 hover:text-error"
                @click="clear()"
                :class="hasInk ? 'visible' : 'invisible'"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22" />
                </svg>
                Clear
            </button>
        </div>

        {{-- Status --}}
        <div class="flex items-center gap-1.5 text-xs">
            <span x-show="hasInk" x-cloak class="inline-flex items-center gap-1 text-success">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Signed
            </span>
            <span x-show="!hasInk" class="text-base-content/30">
                Draw to sign
            </span>
            <span class="text-error" x-show="error" x-cloak x-text="error"></span>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            // ─── signaturePad ────────────────────────────────────────
            // Canvas + pointer-event drawing pad with:
            //   • Quadratic Bézier stroke smoothing
            //   • Stroke history for undo
            //   • Full-width responsive rendering (DPR-aware)
            //   • Draft hydration support
            //
            // Publishes a base64 PNG into the hidden input and pushes
            // the value into the containing Livewire component via
            // comp.set() so wire:model stays in sync despite wire:ignore.
            // ──────────────────────────────────────────────────────────
            window.signaturePad = function (name, w, h) {
                return {
                    name, w, h,
                    dataUrl: '',
                    hasInk: false,
                    error: '',
                    ctx: null,
                    drawing: false,
                    last: null,
                    strokes: [],      // array of ImageData snapshots for undo
                    _dpr: 1,

                    init() {
                        const canvas = this.$refs.canvas;
                        this._dpr = window.devicePixelRatio || 1;

                        // Size canvas for HiDPI
                        canvas.width  = this.w * this._dpr;
                        canvas.height = this.h * this._dpr;
                        canvas.style.width  = '100%';
                        canvas.style.height = this.h + 'px';

                        this.ctx = canvas.getContext('2d', { willReadFrequently: true });
                        this.ctx.scale(this._dpr, this._dpr);
                        this.ctx.lineWidth   = 2.5;
                        this.ctx.lineCap     = 'round';
                        this.ctx.lineJoin    = 'round';
                        this.ctx.strokeStyle = '#1a1a1a';

                        // Listen for draft-restore from parent form
                        window.addEventListener('tsr.signature-hydrated', (ev) => {
                            if (! ev || ! ev.detail) return;
                            if (ev.detail.name !== this.name) return;
                            if (ev.detail.dataUrl) {
                                this.hasInk = true;
                                this.dataUrl = ev.detail.dataUrl;
                                this._drawDataUrl(ev.detail.dataUrl);
                                this.push();
                            }
                        });

                        // Pointer events — save a snapshot before each stroke
                        const onDown = (e) => {
                            e.preventDefault();
                            canvas.setPointerCapture(e.pointerId);
                            this._saveStroke();
                            this.drawing = true;
                            this.last = this.point(e);
                        };

                        const onMove = (e) => {
                            if (! this.drawing) return;
                            e.preventDefault();
                            const p = this.point(e);
                            // Quadratic Bézier smoothing
                            if (this.last && this._prev) {
                                const mx = (this.last.x + p.x) / 2;
                                const my = (this.last.y + p.y) / 2;
                                this.ctx.beginPath();
                                this.ctx.moveTo(this._prev.x, this._prev.y);
                                this.ctx.quadraticCurveTo(this.last.x, this.last.y, mx, my);
                                this.ctx.stroke();
                                this._prev = { x: mx, y: my };
                            } else {
                                // First segment — just draw a dot
                                this.ctx.beginPath();
                                this.ctx.arc(this.last.x, this.last.y, 1.2, 0, Math.PI * 2);
                                this.ctx.fill();
                                this._prev = { ...this.last };
                            }
                            this.last = p;
                            this.hasInk = true;
                        };

                        const onUp = (e) => {
                            if (! this.drawing) return;
                            this.drawing = false;
                            this._prev = null;
                            this.commit();
                        };

                        canvas.addEventListener('pointerdown',   onDown);
                        canvas.addEventListener('pointermove',   onMove);
                        canvas.addEventListener('pointerup',     onUp);
                        canvas.addEventListener('pointercancel', onUp);
                    },

                    /** Convert a pointer event to canvas-local coords. */
                    point(e) {
                        const rect = this.$refs.canvas.getBoundingClientRect();
                        return {
                            x: (e.clientX - rect.left) * (this.w / rect.width),
                            y: (e.clientY - rect.top)  * (this.h / rect.height),
                        };
                    },

                    /** Snapshot the current canvas for undo. */
                    _saveStroke() {
                        const canvas = this.$refs.canvas;
                        const ctx = canvas.getContext('2d');
                        this.strokes.push(ctx.getImageData(0, 0, canvas.width, canvas.height));
                        // Cap history at 30 strokes
                        if (this.strokes.length > 30) this.strokes.shift();
                    },

                    /** Undo the last stroke. */
                    undo() {
                        if (this.strokes.length === 0) return;
                        const canvas = this.$refs.canvas;
                        const ctx = canvas.getContext('2d');
                        const prev = this.strokes.pop();
                        ctx.putImageData(prev, 0, 0);
                        this.hasInk = this.strokes.length > 0;
                        this.commit();
                    },

                    /** Clear the canvas. */
                    clear() {
                        const canvas = this.$refs.canvas;
                        const ctx = canvas.getContext('2d');
                        ctx.clearRect(0, 0, canvas.width, canvas.height);
                        this.hasInk = false;
                        this.dataUrl = '';
                        this.strokes = [];
                        this.commit();
                    },

                    /** Encode the canvas to dataUrl and push to Livewire. */
                    commit() {
                        try {
                            this.dataUrl = this.$refs.canvas.toDataURL('image/png');
                            this.push();
                            window.dispatchEvent(new CustomEvent('tsr.signature-committed', {
                                detail: { name: this.name, dataUrl: this.dataUrl }
                            }));
                        } catch (e) {
                            this.error = 'Could not capture signature: ' + e.message;
                        }
                    },

                    /** Push dataUrl into the Livewire component via set(). */
                    push() {
                        const root = this.$root.closest('[wire\\:id]');
                        if (! root) return;
                        const id = root.getAttribute('wire:id');
                        if (! window.Livewire || ! id) return;
                        const comp = window.Livewire.find(id);
                        if (comp) comp.set(this.name, this.dataUrl || '');
                    },

                    /** Draw a dataUrl back onto the canvas (for draft restore). */
                    _drawDataUrl(dataUrl) {
                        const img = new Image();
                        img.onload = () => {
                            const ctx = this.$refs.canvas.getContext('2d');
                            ctx.clearRect(0, 0, this.$refs.canvas.width, this.$refs.canvas.height);
                            ctx.drawImage(img, 0, 0, this.w, this.h);
                        };
                        img.src = dataUrl;
                    },
                };
            };
        </script>
    @endpush
@endonce
