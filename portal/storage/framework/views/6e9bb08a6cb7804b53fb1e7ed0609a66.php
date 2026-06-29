

<?php
    // Status -> Bootstrap-like color class. Defined here so the
    // view can render a colored chip without a roundtrip.
    $statusColors = [
        'open'        => 'secondary',
        'in_progress' => 'primary',
        'pending'     => 'warning',
        'escalated'   => 'danger',
        'completed'   => 'success',
    ];
    $currentColor = $statusColors[$serviceStatus] ?? 'secondary';
    $statusLabel  = \App\Enums\ServiceStatus::tryFrom($serviceStatus)?->label() ?? $serviceStatus;

    // Friendly duration ("3h 15m" / "45m" / "0m").
    $duration = $totalMinutes > 0
        ? sprintf('%dh %dm', intdiv($totalMinutes, 60), $totalMinutes % 60)
        : '0m';

    // Pre-fill TSP name/email from the auth user so the TSP
    // doesn't have to type it for every report.
    $tspName  = auth()->user()?->name  ?? '';
    $tspEmail = auth()->user()?->email ?? '';
?>

<div
    class="tsr-form"
    x-data="tsrForm()"
    x-init="init()"
>
    
    <div class="tsr-sticky-bar sticky-top d-flex flex-wrap gap-2 align-items-center p-2 mb-3 border rounded bg-white shadow-sm" style="z-index: 1020;">
        <div class="d-flex align-items-center gap-2">
            <span class="badge text-bg-dark fs-6" title="Monday item id of the source ticket">
                #<?php echo e($ticketNumber); ?>

            </span>
            <span class="text-muted small d-none d-md-inline">Ticket #</span>
        </div>

        <div>
            <label for="tsr-service-status" class="form-label small mb-0">Status</label>
            <select
                id="tsr-service-status"
                wire:model.live="serviceStatus"
                class="form-select form-select-sm border-<?php echo e($currentColor); ?> text-<?php echo e($currentColor); ?> fw-semibold"
                style="width: 14ch;"
            >
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = \App\Enums\ServiceStatus::cases(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $case): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($case->value); ?>"><?php echo e($case->label()); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </select>
        </div>

        <div class="d-flex align-items-center gap-1" title="Stable id used for offline-idempotent saves">
            <span class="text-muted small d-none d-lg-inline">Local&nbsp;ID</span>
            <code class="small text-muted user-select-all"
                  style="font-size: .72rem; max-width: 24ch; overflow:hidden; text-overflow:ellipsis;">
                <?php echo e($localId); ?>

            </code>
        </div>

        <div class="d-flex flex-column align-items-start" title="Computed from service start / end">
            <span class="text-muted small lh-1">Duration</span>
            <span class="fw-semibold lh-1" style="font-variant-numeric: tabular-nums;">
                <?php echo e($duration); ?>

            </span>
        </div>

        
        <div class="d-flex align-items-center gap-1" :title="online ? 'Online' : 'Offline'">
            <span
                class="rounded-circle d-inline-block"
                style="width:.65rem; height:.65rem;"
                :class="online ? 'bg-success' : 'bg-secondary'"
            ></span>
            <span class="small text-muted" x-text="online ? 'Online' : 'Offline'">Online</span>
        </div>

        <div class="ms-auto d-flex gap-2 align-items-center">
            
            <span
                class="badge d-inline-flex align-items-center gap-1 tsr-sync-pill"
                :class="syncPillClass"
                :title="syncPillTitle"
                data-pending="0"
                data-syncing="0"
                data-synced="0"
                data-error="0"
                x-ref="syncPill"
            >
                <span x-text="syncPillIcon" aria-hidden="true"></span>
                <span x-text="syncPillLabel"></span>
            </span>
            <button
                type="button"
                class="btn btn-sm"
                :class="forcedOffline ? 'btn-warning' : 'btn-outline-secondary'"
                @click="forceOffline()"
                title="Simulate being offline so the form uses the local queue"
            >
                <span x-show="!forcedOffline">Go offline</span>
                <span x-show="forcedOffline" x-cloak>Back online</span>
            </button>
            <button
                type="button"
                class="btn btn-sm btn-success"
                :disabled="syncInFlight"
                :class="syncInFlight ? 'disabled' : ''"
                @click="manualSync()"
                :title="manualSyncTitle"
            >
                <span x-show="!syncInFlight" x-cloak>☁️ Sync to Monday</span>
                <span x-show="syncInFlight" x-cloak>
                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                    Syncing…
                </span>
            </button>
            <button
                type="submit"
                class="btn btn-primary btn-sm"
                wire:loading.attr="disabled"
                wire:target="submit"
            >
                <span wire:loading.remove wire:target="submit">📤 Submit Report</span>
                <span wire:loading wire:target="submit">Submitting…</span>
            </button>
        </div>
    </div>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($lastError): ?>
        <div class="alert alert-danger py-2 small d-flex align-items-start gap-2" role="alert">
            <span aria-hidden="true">⚠️</span>
            <div>
                <strong>Save failed:</strong> <?php echo e($lastError); ?>

            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('tsr.saved')): ?>
        <div class="alert alert-success py-2 small d-flex align-items-center gap-2" role="status" x-data x-init="queueDrain()">
            <span aria-hidden="true">✅</span>
            <div>
                TSR saved locally.
                <span x-show="online"  x-cloak>Will sync to Monday.com now.</span>
                <span x-show="!online" x-cloak>Will sync when you’re back online.</span>
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    <div
        class="alert alert-info py-2 small d-flex align-items-center justify-content-between gap-2 mb-2"
        role="status"
        x-show="hasDraft && ! _draftRestored"
        x-cloak
    >
        <div class="d-flex align-items-center gap-2">
            <span aria-hidden="true">📝</span>
            <div>
                <strong>Draft found in this browser.</strong>
                Re-opening the form to restore your work in progress.
            </div>
        </div>
        <button
            type="button"
            class="btn btn-sm btn-outline-secondary"
            @click="discardDraft()"
        >
            Discard draft
        </button>
    </div>

    <div
        class="text-muted small d-flex align-items-center gap-1 mb-2"
        x-show="_draftAvailable"
        x-cloak
        title="Your inputs (including the three signature drawings) are being saved to this browser as you type. Close the tab and reopen this ticket to continue."
    >
        <span aria-hidden="true">💾</span>
        <span>Draft autosaved to this browser</span>
    </div>

    <form wire:submit.prevent="submit" novalidate>

        
        <fieldset class="mb-4 tsr-section">
            <legend class="tsr-legend">
                <span class="tsr-legend__icon" aria-hidden="true">🏷️</span>
                Asset
            </legend>
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="form-label small">Machine / System serial</label>
                    <input
                        type="text"
                        wire:model.blur="machineSystemSerialNumber"
                        class="form-control form-control-sm"
                        placeholder="e.g. SN-2024-00123"
                        autocomplete="off"
                    />
                </div>
                <div class="col-md-6">
                    <label class="form-label small">Software version</label>
                    <input
                        type="text"
                        wire:model.blur="softwareVersionNo"
                        class="form-control form-control-sm"
                        placeholder="e.g. v3.2.1"
                        autocomplete="off"
                    />
                </div>
            </div>
        </fieldset>

        
        <fieldset class="mb-4 tsr-section">
            <legend class="tsr-legend">
                <span class="tsr-legend__icon" aria-hidden="true">⏱️</span>
                Timeline
                <span class="text-muted small fw-normal ms-2">
                    Duration: <span class="fw-semibold"><?php echo e($duration); ?></span>
                </span>
            </legend>
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label small">Log in</label>
                    <input
                        type="datetime-local"
                        wire:model.live="logInDate"
                        class="form-control form-control-sm"
                    />
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-primary">Service start <span class="text-danger">*</span></label>
                    <input
                        type="datetime-local"
                        wire:model.live="serviceStartDateTime"
                        class="form-control form-control-sm border-primary-subtle"
                        required
                    />
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-primary">Service end <span class="text-danger">*</span></label>
                    <input
                        type="datetime-local"
                        wire:model.live="serviceEndDateTime"
                        class="form-control form-control-sm border-primary-subtle"
                        required
                    />
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Log out</label>
                    <input
                        type="datetime-local"
                        wire:model.live="logOutDate"
                        class="form-control form-control-sm"
                    />
                </div>
            </div>
            <div class="form-text small mt-1">
                <span x-data
                      x-init="$nextTick(() => {
                          if (! $wire.get('serviceStartDateTime')) {
                              const d = new Date();
                              d.setSeconds(0, 0);
                              const pad = n => String(n).padStart(2,'0');
                              $wire.set('serviceStartDateTime',
                                  d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate()) +
                                  'T' + pad(d.getHours()) + ':' + pad(d.getMinutes()));
                          }
                      })"
                      x-show="! $wire.get('serviceStartDateTime')"
                      x-cloak>
                    Tip: start typing — we'll prefill Service start to "now" if it's still empty.
                </span>
            </div>
        </fieldset>

        
        <fieldset class="mb-4 tsr-section">
            <legend class="tsr-legend">
                <span class="tsr-legend__icon" aria-hidden="true">📝</span>
                Narrative
            </legend>
            <div class="row g-2">
                <?php
                    $narrative = [
                        ['wire' => 'problemAndConcerns', 'label' => 'Problem & concerns', 'icon' => '❗', 'required' => true,  'ph' => "What did the customer report? What's wrong?"],
                        ['wire' => 'jobDone',            'label' => 'Job done',            'icon' => '✅', 'required' => true,  'ph' => 'What did you do to fix it?'],
                        ['wire' => 'partsReplaced',      'label' => 'Parts replaced',      'icon' => '🔧', 'required' => false, 'ph' => 'List part numbers / quantities (or "none")'],
                        ['wire' => 'recommendation',     'label' => 'Recommendation',     'icon' => '💡', 'required' => false, 'ph' => 'Follow-up recommended? Training needed?'],
                        ['wire' => 'remarks',            'label' => 'Remarks',            'icon' => '🗒️', 'required' => false, 'ph' => 'Anything else worth recording'],
                    ];
                ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $narrative; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="col-12">
                        <label class="form-label small d-flex justify-content-between">
                            <span>
                                <span aria-hidden="true"><?php echo e($row['icon']); ?></span>
                                <?php echo e($row['label']); ?>

                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($row['required']): ?> <span class="text-danger">*</span> <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </span>
                            <span class="text-muted">
                                <span x-data="{ count: 0, max: 5000 }"
                                      x-init="
                                          count = ($wire.get('<?php echo e($row['wire']); ?>') || '').length;
                                      "
                                      @input.window="count = ($wire.get('<?php echo e($row['wire']); ?>') || '').length"
                                      :class="count > max ? 'text-danger fw-semibold' : ''">
                                    <span x-text="count"></span>/<span x-text="max"></span>
                                </span>
                            </span>
                        </label>
                        <textarea
                            wire:model.live="<?php echo e($row['wire']); ?>"
                            class="form-control form-control-sm <?php if($row['required']): ?> border-primary-subtle <?php endif; ?>"
                            rows="2"
                            maxlength="5000"
                            placeholder="<?php echo e($row['ph']); ?>"
                            <?php if($row['required']): ?> required <?php endif; ?>
                        ></textarea>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </fieldset>

        
        <fieldset class="mb-4 tsr-section">
            <legend class="tsr-legend">
                <span class="tsr-legend__icon" aria-hidden="true">🖋️</span>
                TSP signature
            </legend>
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small">
                        TSP name <span class="text-danger">*</span>
                    </label>
                    <input
                        type="text"
                        wire:model="tspSignatureName"
                        class="form-control form-control-sm"
                        placeholder="<?php echo e($tspName); ?>"
                        required
                    />
                </div>
                <div class="col-md-8">
                    <?php if (isset($component)) { $__componentOriginal72332feea9f878ab2343bb6e35d6719d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal72332feea9f878ab2343bb6e35d6719d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.signature-pad','data' => ['name' => 'tspSignatureDataUrl','width' => 500,'height' => 140]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('signature-pad'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'tspSignatureDataUrl','width' => 500,'height' => 140]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal72332feea9f878ab2343bb6e35d6719d)): ?>
<?php $attributes = $__attributesOriginal72332feea9f878ab2343bb6e35d6719d; ?>
<?php unset($__attributesOriginal72332feea9f878ab2343bb6e35d6719d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal72332feea9f878ab2343bb6e35d6719d)): ?>
<?php $component = $__componentOriginal72332feea9f878ab2343bb6e35d6719d; ?>
<?php unset($__componentOriginal72332feea9f878ab2343bb6e35d6719d); ?>
<?php endif; ?>
                </div>
            </div>
        </fieldset>

        
        <fieldset class="mb-4 tsr-section">
            <legend class="tsr-legend">
                <span class="tsr-legend__icon" aria-hidden="true">👤</span>
                Customer in charge
            </legend>
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small">Full name <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        wire:model="customerName"
                        class="form-control form-control-sm"
                        required
                    />
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Email <span class="text-danger">*</span></label>
                    <input
                        type="email"
                        wire:model="customerEmail"
                        class="form-control form-control-sm"
                        required
                    />
                </div>
                <div class="col-md-4">
                    <?php if (isset($component)) { $__componentOriginal72332feea9f878ab2343bb6e35d6719d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal72332feea9f878ab2343bb6e35d6719d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.signature-pad','data' => ['name' => 'customerSignatureDataUrl','width' => 500,'height' => 140]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('signature-pad'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'customerSignatureDataUrl','width' => 500,'height' => 140]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal72332feea9f878ab2343bb6e35d6719d)): ?>
<?php $attributes = $__attributesOriginal72332feea9f878ab2343bb6e35d6719d; ?>
<?php unset($__attributesOriginal72332feea9f878ab2343bb6e35d6719d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal72332feea9f878ab2343bb6e35d6719d)): ?>
<?php $component = $__componentOriginal72332feea9f878ab2343bb6e35d6719d; ?>
<?php unset($__componentOriginal72332feea9f878ab2343bb6e35d6719d); ?>
<?php endif; ?>
                </div>
            </div>
        </fieldset>

        
        <fieldset class="mb-4 tsr-section">
            <legend class="tsr-legend">
                <span class="tsr-legend__icon" aria-hidden="true">🧑‍⚕️</span>
                BIOMED in charge
            </legend>
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small">Name <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        wire:model="biomedName"
                        class="form-control form-control-sm"
                        required
                    />
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Email <span class="text-danger">*</span></label>
                    <input
                        type="email"
                        wire:model="biomedEmail"
                        class="form-control form-control-sm"
                        required
                    />
                </div>
                <div class="col-md-4">
                    <?php if (isset($component)) { $__componentOriginal72332feea9f878ab2343bb6e35d6719d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal72332feea9f878ab2343bb6e35d6719d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.signature-pad','data' => ['name' => 'biomedSignatureDataUrl','width' => 500,'height' => 140]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('signature-pad'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'biomedSignatureDataUrl','width' => 500,'height' => 140]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal72332feea9f878ab2343bb6e35d6719d)): ?>
<?php $attributes = $__attributesOriginal72332feea9f878ab2343bb6e35d6719d; ?>
<?php unset($__attributesOriginal72332feea9f878ab2343bb6e35d6719d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal72332feea9f878ab2343bb6e35d6719d)): ?>
<?php $component = $__componentOriginal72332feea9f878ab2343bb6e35d6719d; ?>
<?php unset($__componentOriginal72332feea9f878ab2343bb6e35d6719d); ?>
<?php endif; ?>
                </div>
            </div>
        </fieldset>

        
        <fieldset class="mb-4 tsr-section">
            <legend class="tsr-legend">
                <span class="tsr-legend__icon" aria-hidden="true">👥</span>
                Co-TSPs <span class="text-muted small fw-normal ms-1">(optional)</span>
            </legend>
            <input
                type="text"
                wire:model.live="tspWorkWithCsv"
                class="form-control form-control-sm"
                placeholder="Monday person ids, comma-separated (e.g. 77787515, 77787561)"
                inputmode="numeric"
                autocomplete="off"
            />
            <div class="d-flex justify-content-between mt-1">
                <small class="text-muted">Leave blank if no co-TSPs.</small>
                <small class="text-muted"
                       x-data="{ n: 0 }"
                       x-init="
                           n = ($wire.get('tspWorkWithCsv') || '').split(',').map(s => s.trim()).filter(Boolean).length;
                       "
                       :class="n > 0 ? 'text-primary fw-semibold' : ''">
                    <span x-text="n"></span> co-TSP<span x-show="n !== 1">s</span> selected
                </small>
            </div>
        </fieldset>

        
        <div class="d-flex justify-content-between align-items-center gap-2 mt-4 pt-3 border-top flex-wrap">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span
                    class="badge d-inline-flex align-items-center gap-1 tsr-sync-pill"
                    :class="syncPillClass"
                    :title="syncPillTitle"
                >
                    <span x-text="syncPillIcon" aria-hidden="true"></span>
                    <span x-text="syncPillLabel"></span>
                </span>
                <small class="text-muted" x-show="lastSyncedAt" x-cloak>
                    Last synced: <span x-text="lastSyncedHuman"></span>
                </small>
                <small class="text-muted"
                       x-show="online && !syncInFlight && (pending > 0 || error > 0)"
                       x-cloak>
                    <span aria-hidden="true">⟳</span> Auto-syncing when ready…
                </small>
            </div>
            <div class="d-flex gap-2">
                <button
                    type="button"
                    class="btn btn-outline-secondary"
                    @click="window.scrollTo({top: 0, behavior: 'smooth'})"
                >
                    ↑ Back to top
                </button>
            </div>
        </div>
    </form>

    <?php if (! $__env->hasRenderedOnce('d79bed31-3694-42e4-a836-da4bd2bcbd7c')): $__env->markAsRenderedOnce('d79bed31-3694-42e4-a836-da4bd2bcbd7c'); ?>
        <?php $__env->startPush('scripts'); ?>
            
            <script src="<?php echo e(asset('js/portal/offline-tsr.js')); ?>" defer></script>
            <script>
                window.__tsrLocalId = <?php echo json_encode($localId, 15, 512) ?>;
                window.__tsrTicketNumber = <?php echo json_encode($ticketNumber, 15, 512) ?>;
                window.__tspName  = <?php echo json_encode($tspName, 15, 512) ?>;
                window.__tspEmail = <?php echo json_encode($tspEmail, 15, 512) ?>;
                window.__tsrSyncUrl   = <?php echo json_encode(route('tsp.tickets.tsr.sync', ['id' => $ticketNumber]), 512) ?>;
                window.__tsrStatusUrl = <?php echo json_encode(route('tsp.tickets.tsr.status', ['id' => $ticketNumber]), 512) ?>;
            </script>
            <style>
                /* Make the sticky bar sit just below the global
                   navigation. Adjust the offset if your nav is a
                   different height. */
                .tsr-sticky-bar { top: 4.5rem; }
                @media (max-width: 768px) {
                    .tsr-sticky-bar { top: 5.5rem; }
                }
                .tsr-section {
                    border: 1px solid #e5e7eb;
                    border-radius: .5rem;
                    padding: .9rem 1rem .6rem;
                    background: #fff;
                }
                .tsr-legend {
                    display: inline-flex;
                    align-items: center;
                    gap: .35rem;
                    font-size: .95rem;
                    font-weight: 600;
                    width: auto;
                    padding: 0 .5rem;
                    margin: 0 0 .5rem -.5rem;
                    background: #f8fafc;
                    border: 1px solid #e5e7eb;
                    border-radius: .35rem;
                }
                .tsr-legend__icon { font-size: 1rem; }
                [x-cloak] { display: none !important; }
                /* Make sure the sticky bar doesn't overlap the page
                   heading on small screens. */
                body { scroll-padding-top: 6rem; }
            </style>
            <script>
                // ----------------------------------------------------------------
                //  tsrForm — Alpine component wrapping the Livewire TSR form.
                //  Owns:
                //    * online / offline detection + a manual "force offline"
                //      toggle for testing
                //    * per-row sync state (pending / syncing / synced / error)
                //      polled from the server every 5s
                //    * a "Sync to Monday" button that POSTs the drainer
                //      endpoint, plus an auto-drain on the `online` event
                //    * the friendly "X queued / Y synced" pill on the
                //      sticky bar
                //    * smooth-scroll to the first error on submit failure
                //    * pre-filling the TSP signature name on first focus
                // ----------------------------------------------------------------
                window.tsrForm = function () {
                    return {
                        // Connection state
                        online: (typeof navigator !== 'undefined' ? navigator.onLine : true),
                        forcedOffline: false,

                        // Sync state (counts per state for this ticket)
                        pending: 0,
                        syncing: 0,
                        synced:  0,
                        error:   0,
                        lastError: null,
                        lastSyncedAt: null,
                        syncInFlight: false,
                        _statusTimer: null,
                        _initialized: false,

                        // Draft autosave (localStorage). Saves a snapshot
                        // of the form on every meaningful change so the
                        // user can close the browser, lose connection, or
                        // accidentally refresh without losing their work.
                        _draftSaveTimer: null,
                        _draftRestored: false,
                        _draftDiscarding: false,
                        _draftUserTouched: false,
                        _hydrating: false,

                        // ─── Lifecycle ───
                        init() {
                            if (this._initialized) return;
                            this._initialized = true;

                            // Wire online/offline listeners.
                            const update = () => {
                                if (! this.forcedOffline) {
                                    this.online = navigator.onLine;
                                }
                                if (this.online) {
                                    // We just came online (or started
                                    // online). Kick off a drain so any
                                    // queued TSRs for this ticket get
                                    // pushed to Monday right away. The
                                    // drain is a no-op when there's
                                    // nothing pending — see queueDrain().
                                    this.queueDrain();
                                }
                            };
                            window.addEventListener('online',  update);
                            window.addEventListener('offline', update);

                            // Initial status fetch, then poll every 5s.
                            // The poll ALSO triggers a drain whenever
                            // it sees pending/error work and we're
                            // online — that catches the case where the
                            // tab was open across a connection drop and
                            // the browser didn't fire an `online` event
                            // (some browsers don't when the network
                            // restores from sleep / VPN reconnect).
                            this.fetchStatus();
                            this._statusTimer = setInterval(() => {
                                this.fetchStatus().then(() => {
                                    if (this.online
                                        && ! this.syncInFlight
                                        && (this.pending > 0 || this.error > 0)
                                    ) {
                                        this.queueDrain();
                                    }
                                });
                            }, 5000);

                            // Stop the timer when the form is torn down.
                            this.$el.addEventListener('tsr.teardown', () => {
                                if (this._statusTimer) clearInterval(this._statusTimer);
                            });

                            // ── Draft autosave wiring ──
                            // Hydrate from localStorage (if any) AFTER
                            // Livewire has had a chance to mount and
                            // expose the wire id on the root.
                            //
                            // Key the draft on the TICKET NUMBER, not
                            // the random localId. localId is regenerated
                            // by the server on every request, so a
                            // key derived from it would orphan the
                            // draft across reloads. The ticket number
                            // is stable per ticket and unique enough
                            // for this purpose.
                            this._draftKey = 'tsr.draft.' + (window.__tsrTicketNumber || window.__tsrLocalId || 'unknown');

                            // Hydrate IMMEDIATELY (synchronously) so
                            // the form is populated before any save
                            // listener can fire. If we waited for
                            // `livewire:init` or a setTimeout, the
                            // Livewire `livewire:updated` event would
                            // land first and clobber the stored draft
                            // with the (empty) current form values.
                            this._hydrateFromDraft();

                            // Save on any Livewire property update
                            // (debounced). The 'livewire:updated' event
                            // fires after Livewire commits a set() call,
                            // which covers every wire:model roundtrip.
                            window.addEventListener('livewire:updated', () => this._scheduleDraftSave());

                            // Fallback: also listen for DOM input/change
                            // events on the form root. The form is
                            // wrapped in `wire:ignore.self` (so Alpine
                            // can fully control the signature pads
                            // without Livewire trying to re-render the
                            // canvas), which means wire:model on text
                            // inputs is intentionally inert. The
                            // `livewire:updated` event therefore does
                            // not fire when the user types, so we also
                            // hook the native input event for any
                            // input/textarea/select inside the form.
                            const root = this.$el;
                            if (root) {
                                // The `input`/`change` events are
                                // fired by the user actually editing
                                // the form (not by Livewire's initial
                                // mount), so they're a safe signal to
                                // mark the draft as "user-touched" and
                                // enable autosave. Livewire's
                                // `livewire:updated` event below is
                                // NOT used to mark touched, because
                                // it also fires on initial mount.
                                //
                                // We also ignore events fired while
                                // we're re-hydrating the form, so the
                                // programmatic `el.dispatchEvent(...)`
                                // calls in `_hydrateFromDraft` do NOT
                                // mark the form as user-touched
                                // (otherwise a freshly-restored draft
                                // would overwrite itself on the next
                                // autosave tick).
                                const markTouched = () => {
                                    if (this._hydrating) return;
                                    this._draftUserTouched = true;
                                    this._scheduleDraftSave();
                                };
                                root.addEventListener('input',  markTouched);
                                root.addEventListener('change', markTouched);
                            }

                            // Save on signature-pad commits.
                            window.addEventListener('tsr.signature-committed', () => this._scheduleDraftSave());

                            // Final save on page hide (covers refresh
                            // and tab close). navigator.sendBeacon is
                            // not appropriate here because the data is
                            // already in localStorage; we just make
                            // sure the timer doesn't swallow it.
                            // The save only runs if the user has
                            // actually touched the form — otherwise
                            // a reload right after page load would
                            // overwrite a stored draft with the
                            // (empty) current form values.
                            window.addEventListener('pagehide', () => {
                                if (! this._draftUserTouched) return;
                                if (this._draftSaveTimer) {
                                    clearTimeout(this._draftSaveTimer);
                                    this._draftSaveTimer = null;
                                }
                                this._saveDraftNow();
                            });

                            // Clear the draft once the Livewire submit
                            // succeeds (the component dispatches
                            // 'tsr.saved' on success).
                            window.addEventListener('tsr.saved', (ev) => {
                                if (ev && ev.detail && ev.detail.localId
                                    && ev.detail.localId !== window.__tsrLocalId) {
                                    return; // not our ticket
                                }
                                this._clearDraft();
                            });
                        },

                        // ─── Draft autosave ───
                        get _draftAvailable() {
                            try {
                                return typeof window !== 'undefined'
                                    && !! window.localStorage;
                            } catch (e) { return false; }
                        },
                        get hasDraft() {
                            if (! this._draftAvailable) return false;
                            try { return !! window.localStorage.getItem(this._draftKey); }
                            catch (e) { return false; }
                        },
                        _scheduleDraftSave() {
                            if (! this._draftAvailable) return;
                            if (this._draftDiscarding) return;
                            // Don't clobber the stored draft with the
                            // initial (empty) form state. The
                            // `livewire:updated` event fires on
                            // mount even though the user hasn't
                            // touched anything, and saving then
                            // would overwrite a draft the user
                            // might be about to recover.
                            if (! this._draftUserTouched) return;
                            if (this._draftSaveTimer) clearTimeout(this._draftSaveTimer);
                            this._draftSaveTimer = setTimeout(() => this._saveDraftNow(), 400);
                        },
                        _saveDraftNow() {
                            this._draftSaveTimer = null;
                            if (! this._draftAvailable) return;
                            if (this._draftDiscarding) return;
                            // Belt-and-braces: if the user hasn't
                            // touched the form yet, do not clobber a
                            // stored draft. (Should already be
                            // prevented by the schedule guard and the
                            // pagehide guard, but we keep this as a
                            // safety net for any future caller.)
                            if (! this._draftUserTouched) return;
                            try {
                                // The form is wrapped in `wire:ignore.self`,
                                // so Livewire's `getData()` does NOT
                                // reflect the user's input — wire:model
                                // is intentionally inert. We read the
                                // values straight out of the DOM
                                // (inputs / textareas / selects) so
                                // the draft mirrors what the user sees.
                                const fields = {};
                                if (this.$el) {
                                    const controls = this.$el.querySelectorAll(
                                        'input[name], textarea[name], select[name], ' +
                                        'input[wire\\:model], textarea[wire\\:model], select[wire\\:model]'
                                    );
                                    controls.forEach((el) => {
                                        const key = el.getAttribute('wire:model')
                                                 || el.name
                                                 || el.id;
                                        if (! key) return;
                                        // Hidden inputs that carry
                                        // signature dataUrls are
                                        // captured separately under
                                        // `signatures` — don't duplicate.
                                        if (el.type === 'hidden' && /SignatureDataUrl$/.test(key)) {
                                            return;
                                        }
                                        let val;
                                        if (el.type === 'checkbox') {
                                            val = !! el.checked;
                                        } else if (el.type === 'radio') {
                                            if (el.checked) val = el.value;
                                            else return;
                                        } else {
                                            val = el.value;
                                        }
                                        if (val === undefined) return;
                                        fields[key] = val;
                                    });
                                }
                                // No Livewire fallback: the form is
                                // `wire:ignore.self`, so Livewire never
                                // sees any of these inputs anyway. Calling
                                // `comp.getData()` (a Livewire 2 method)
                                // throws `MethodNotFoundException` on
                                // Livewire 3, so we deliberately read
                                // everything from the DOM and stop here.

                                // Signatures are stored in hidden
                                // inputs that the Alpine signature-pad
                                // owns via x-model. Pull them straight
                                // from the DOM (wire:ignore.self means
                                // they don't make it into Livewire
                                // state via wire:model).
                                const readSig = (name) => {
                                    if (! this.$el) return '';
                                    const el = this.$el.querySelector(`input[name="${name}"]`);
                                    return (el && el.value) || '';
                                };
                                const sigs = {
                                    tspSignatureDataUrl:      readSig('tspSignatureDataUrl'),
                                    customerSignatureDataUrl: readSig('customerSignatureDataUrl'),
                                    biomedSignatureDataUrl:   readSig('biomedSignatureDataUrl'),
                                };
                                // Promote the dataUrls into fields too
                                // so a future roundtrip can still
                                // restore the values without needing a
                                // separate hydrate path.
                                if (sigs.tspSignatureDataUrl)      fields.tspSignatureDataUrl      = sigs.tspSignatureDataUrl;
                                if (sigs.customerSignatureDataUrl) fields.customerSignatureDataUrl = sigs.customerSignatureDataUrl;
                                if (sigs.biomedSignatureDataUrl)   fields.biomedSignatureDataUrl   = sigs.biomedSignatureDataUrl;
                                const draft = {
                                    v:           1,
                                    savedAt:     Date.now(),
                                    localId:     window.__tsrLocalId || null,
                                    ticket:      window.__tsrTicketNumber || null,
                                    fields:      fields,
                                    signatures:  sigs,
                                };
                                window.localStorage.setItem(this._draftKey, JSON.stringify(draft));
                            } catch (e) {
                                // Quota / serialization errors are
                                // non-fatal — the form still works,
                                // we just can't autosave.
                                console.warn('tsr draft save failed:', e);
                            }
                        },
                        _hydrateFromDraft() {
                            if (! this._draftAvailable) return;
                            let raw;
                            try { raw = window.localStorage.getItem(this._draftKey); }
                            catch (e) { return; }
                            if (! raw) return;
                            let draft;
                            try { draft = JSON.parse(raw); } catch (e) { return; }
                            if (! draft || ! draft.fields) return;

                            // Sanity-check: draft must match the
                            // ticket we're on. (We do NOT compare
                            // localId here — it's a random uuid that
                            // the server regenerates on every
                            // request, so a draft from a previous
                            // request would never match. The ticket
                            // number is the only stable identifier.)
                            if (draft.ticket && window.__tsrTicketNumber
                                && String(draft.ticket) !== String(window.__tsrTicketNumber)) {
                                return;
                            }

                            const root = this.$root.closest('[wire\\:id]');
                            if (! root) return;
                            const id = root.getAttribute('wire:id');
                            const comp = (window.Livewire && id) ? window.Livewire.find(id) : null;

                            // Push text/numeric fields back to the DOM
                            // first (the form is wrapped in
                            // `wire:ignore.self`, so wire:model is
                            // intentionally inert and Livewire.set()
                            // alone would not change what the user
                            // sees). We then also try comp.set() so
                            // the server-side state matches.
                            const skip = new Set([
                                'tspSignatureDataUrl',
                                'customerSignatureDataUrl',
                                'biomedSignatureDataUrl',
                            ]);
                            const findControl = (key) => {
                                if (! this.$el) return null;
                                return this.$el.querySelector(
                                    `[wire\\:model="${key}"], [name="${key}"]`
                                );
                            };
                            this._hydrating = true;
                            try {
                            for (const [k, v] of Object.entries(draft.fields)) {
                                if (skip.has(k)) continue;
                                if (v === undefined || v === null) continue;
                                const el = findControl(k);
                                if (el) {
                                    try {
                                        if (el.type === 'checkbox') {
                                            el.checked = !! v;
                                        } else if (el.type === 'radio') {
                                            el.checked = (el.value === String(v));
                                        } else {
                                            el.value = v;
                                        }
                                        // Defer event dispatch to the
                                        // next tick so listeners that
                                        // would clobber the value (e.g.
                                        // a Livewire/Alpine sync) get a
                                        // chance to run AFTER we've set
                                        // it. The `input` event is what
                                        // marks the form as
                                        // user-touched in our own
                                        // listener, so the draft
                                        // autosave doesn't trigger a
                                        // roundtrip immediately.
                                        setTimeout(() => {
                                            el.dispatchEvent(new Event('input',  { bubbles: true }));
                                            el.dispatchEvent(new Event('change', { bubbles: true }));
                                        }, 0);
                                    } catch (e) { /* ignore */ }
                                }
                                if (comp && typeof comp.set === 'function') {
                                    try { comp.set(k, v); } catch (e) { /* ignore */ }
                                }
                            }
                            } finally {
                                this._hydrating = false;
                            }

                            // Restore signatures onto the canvases.
                            this.$nextTick(() => {
                                if (draft.signatures) {
                                    if (draft.signatures.tspSignatureDataUrl) {
                                        this._restoreSignature('tspSignatureDataUrl',      draft.signatures.tspSignatureDataUrl);
                                    }
                                    if (draft.signatures.customerSignatureDataUrl) {
                                        this._restoreSignature('customerSignatureDataUrl', draft.signatures.customerSignatureDataUrl);
                                    }
                                    if (draft.signatures.biomedSignatureDataUrl) {
                                        this._restoreSignature('biomedSignatureDataUrl',   draft.signatures.biomedSignatureDataUrl);
                                    }
                                }
                            });

                            this._draftRestored = true;
                            window.dispatchEvent(new CustomEvent('tsr.draft-restored', { detail: { localId: window.__tsrLocalId } }));
                        },
                        _restoreSignature(name, dataUrl) {
                            try {
                                // Find the canvas that belongs to the
                                // hidden input with this name. The
                                // signature-pad component puts them
                                // inside the same .signature-pad root.
                                const root = this.$root.closest('[wire\\:id]');
                                if (! root) return;
                                const inputs = root.querySelectorAll('input[type="hidden"]');
                                for (const inp of inputs) {
                                    if (inp.getAttribute('name') !== name) continue;
                                    const padRoot = inp.closest('.signature-pad');
                                    if (! padRoot) break;
                                    // Alpine owns the canvas; we read
                                    // it back from the data attribute
                                    // it stamped in init() (we add it
                                    // in the patch below). Fall back to
                                    // walking the DOM.
                                    const canvas = padRoot.querySelector('canvas');
                                    if (! canvas) break;
                                    const ctx = canvas.getContext('2d');
                                    const img = new Image();
                                    img.onload = () => {
                                        ctx.clearRect(0, 0, canvas.width, canvas.height);
                                        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                                        // Tell the Alpine pad instance
                                        // that the canvas now has ink so
                                        // its `hasInk` flag is honest.
                                        // The pad listens for this
                                        // custom event.
                                        window.dispatchEvent(new CustomEvent('tsr.signature-hydrated', {
                                            detail: { name: name, dataUrl: dataUrl }
                                        }));
                                    };
                                    img.src = dataUrl;
                                    break;
                                }
                            } catch (e) { /* ignore */ }
                        },
                        discardDraft() {
                            this._draftDiscarding = true;
                            this._clearDraft();
                            // Reload to start from a clean state.
                            window.location.reload();
                        },
                        _clearDraft() {
                            if (! this._draftAvailable) return;
                            try { window.localStorage.removeItem(this._draftKey); }
                            catch (e) { /* ignore */ }
                        },

                        // ─── Sync-state computed pill ───
                        get syncPillClass() {
                            if (this.error   > 0)            return 'bg-danger';
                            if (this.syncing > 0)            return 'bg-info text-dark';
                            if (this.pending > 0)            return 'bg-warning text-dark';
                            if (this.synced  > 0)            return 'bg-success';
                            return 'bg-secondary';
                        },
                        get syncPillIcon() {
                            if (this.error   > 0)            return '⚠';
                            if (this.syncing > 0)            return '↻';
                            if (this.pending > 0)            return '◌';
                            if (this.synced  > 0)            return '✓';
                            return '·';
                        },
                        get syncPillLabel() {
                            if (this.error   > 0)            return this.error   + ' sync failed';
                            if (this.syncing > 0)            return 'Syncing to Monday…';
                            if (this.pending > 0)            return this.pending + ' queued';
                            if (this.synced  > 0)            return 'Synced to Monday';
                            return 'No TSR yet';
                        },
                        get syncPillTitle() {
                            const parts = [
                                this.pending > 0 ? (this.pending + ' pending') : null,
                                this.syncing > 0 ? (this.syncing + ' syncing') : null,
                                this.synced  > 0 ? (this.synced  + ' synced')  : null,
                                this.error   > 0 ? (this.error   + ' error')   : null,
                            ].filter(Boolean);
                            if (parts.length === 0) return 'No TSRs for this ticket yet';
                            if (this.lastError) {
                                return parts.join(' · ') + '\\nLast error: ' + this.lastError;
                            }
                            return parts.join(' · ');
                        },
                        get manualSyncTitle() {
                            if (this.syncInFlight) return 'Sync in progress…';
                            if (this.error > 0)     return 'Retry sending the failed TSRs to Monday.com';
                            if (this.pending > 0)   return 'Send the queued TSR to Monday.com now';
                            if (! this.online)      return 'You appear to be offline — connect first';
                            return 'Force a re-sync of all TSRs for this ticket';
                        },
                        get lastSyncedHuman() {
                            if (! this.lastSyncedAt) return '';
                            try {
                                const d = new Date(this.lastSyncedAt);
                                return d.toLocaleString();
                            } catch (e) { return this.lastSyncedAt; }
                        },

                        // ─── Connection actions ───
                        forceOffline() {
                            this.forcedOffline = ! this.forcedOffline;
                            this.online = ! this.forcedOffline;
                            if (this.online) this.queueDrain();
                        },

                        queueDrain() {
                            // Fire-and-forget: ask the server to drain any
                            // pending TSRs for this ticket. We don't need
                            // to wait for the response — fetchStatus() will
                            // pick up the new state on its next tick.
                            this.manualSync(true /* silent */);
                        },

                        async manualSync(silent = false) {
                            if (this.syncInFlight) return;
                            if (! this.online) {
                                if (! silent) {
                                    window.dispatchEvent(new CustomEvent('tsr.sync_blocked_offline'));
                                }
                                return;
                            }
                            this.syncInFlight = true;
                            try {
                                const r = await fetch(
                                    window.__tsrSyncUrl || '/tsp/tickets/' + (window.__tsrTicketNumber || '') + '/tsr/sync',
                                    {
                                        method: 'POST',
                                        headers: {
                                            'X-Requested-With': 'XMLHttpRequest',
                                            'Accept': 'application/json',
                                        },
                                        credentials: 'same-origin',
                                    }
                                );
                                if (r.ok) {
                                    window.dispatchEvent(new CustomEvent('tsr.synced', {
                                        detail: await r.json().catch(() => ({})),
                                    }));
                                } else {
                                    this.lastError = 'Server returned ' + r.status;
                                }
                            } catch (e) {
                                this.lastError = e.message || 'Network error';
                            } finally {
                                this.syncInFlight = false;
                                // Refresh the pill right after a manual sync
                                // so the user sees the new state immediately.
                                this.fetchStatus();
                            }
                        },

                        async fetchStatus() {
                            const ticket = window.__tsrTicketNumber;
                            if (! ticket) return;
                            try {
                                const r = await fetch(
                                    (window.__tsrStatusUrl || '/tsp/tickets/' + ticket + '/tsr/status'),
                                    {
                                        headers: { 'Accept': 'application/json' },
                                        credentials: 'same-origin',
                                    }
                                );
                                if (! r.ok) return;
                                const data = await r.json();
                                if (! data || data.ok === false) return;
                                this.pending       = data.pending   || 0;
                                this.syncing       = data.syncing   || 0;
                                this.synced        = data.synced    || 0;
                                this.error         = data.error     || 0;
                                this.lastError     = data.last_error || null;
                                this.lastSyncedAt  = data.last_synced_at || null;
                                // Update data-* attrs (useful for e2e tests).
                                const el = this.$refs.syncPill;
                                if (el) {
                                    el.setAttribute('data-pending', this.pending);
                                    el.setAttribute('data-syncing', this.syncing);
                                    el.setAttribute('data-synced',  this.synced);
                                    el.setAttribute('data-error',   this.error);
                                }
                            } catch (e) { /* silent — next tick will retry */ }
                        },
                    };
                };
            </script>
        <?php $__env->stopPush(); ?>
    <?php endif; ?>
</div>
<?php /**PATH C:\Users\USER\Documents\MONDAY.COM\Web Side Project\customer-portal\portal\resources\views/livewire/tsp/tickets/create-service-report.blade.php ENDPATH**/ ?>