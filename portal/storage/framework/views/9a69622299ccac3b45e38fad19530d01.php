<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'name',                  // hidden input name + Livewire property name
    'width'   => 400,
    'height'  => 120,
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'name',                  // hidden input name + Livewire property name
    'width'   => 400,
    'height'  => 120,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div
    class="signature-pad"
    x-data="signaturePad(<?php echo \Illuminate\Support\Js::from($name)->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($width)->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($height)->toHtml() ?>)"
    x-init="init()"
    wire:ignore
>
    <canvas
        x-ref="canvas"
        width="<?php echo e($width); ?>"
        height="<?php echo e($height); ?>"
        class="signature-pad__canvas border rounded bg-white"
        style="touch-action: none; max-width: 100%;"
    ></canvas>

    <input
        type="hidden"
        x-model="dataUrl"
        :name="name"
    />

    <div class="d-flex gap-2 mt-1">
        <button type="button" class="btn btn-sm btn-outline-secondary" @click="clear()">
            Clear
        </button>
        <small class="text-muted align-self-center" x-show="hasInk" x-cloak>
            <span x-text="byteSize"></span> bytes captured
        </small>
        <small class="text-danger align-self-center" x-show="error" x-cloak x-text="error"></small>
    </div>
</div>

<?php if (! $__env->hasRenderedOnce('13c5395f-bb5b-4565-b9ce-cc40b7324ad6')): $__env->markAsRenderedOnce('13c5395f-bb5b-4565-b9ce-cc40b7324ad6'); ?>
    <?php $__env->startPush('scripts'); ?>
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
    <?php $__env->stopPush(); ?>
<?php endif; ?>
<?php /**PATH C:\Users\USER\Documents\MONDAY.COM\Web Side Project\customer-portal\portal\resources\views/components/signature-pad.blade.php ENDPATH**/ ?>