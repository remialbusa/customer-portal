<?php
    /** @var \App\Models\User $user */
    $user = auth()->user();
    $pendingRequest = \App\Models\AccountDeletionRequest::latestPendingFor($user->id);
?>

<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            <?php echo e(__('Delete Account')); ?>

        </h2>

        <p class="mt-1 text-sm text-gray-600">
            <?php echo e(__('Account deletion is handled by our superadmin team to keep your ticket history, chat logs, and audit trail safe. Submit a request below and they will review it and confirm by email.')); ?>

        </p>
    </header>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('status')): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-md px-4 py-3 text-sm">
            <?php echo e(session('status')); ?>

        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($errors->any()): ?>
        <div class="bg-red-50 border border-red-200 text-red-800 rounded-md px-4 py-3 text-sm">
            <ul class="list-disc list-inside space-y-1">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </ul>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($pendingRequest): ?>
        
        <div class="bg-amber-50 border border-amber-200 text-amber-900 rounded-md p-4 text-sm space-y-3">
            <p>
                <span class="font-semibold">Request pending.</span>
                You submitted an account-deletion request on
                <?php echo e($pendingRequest->created_at->format('M j, Y g:i A')); ?>.
                A superadmin will review it shortly.
            </p>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($pendingRequest->reason): ?>
                <p class="text-xs">
                    <span class="font-semibold">Reason you provided:</span>
                    <span class="italic"><?php echo e($pendingRequest->reason); ?></span>
                </p>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <form
                method="POST"
                action="<?php echo e(route('profile.deletion-request.cancel')); ?>"
                onsubmit="return confirm('Cancel your account-deletion request?');"
            >
                <?php echo csrf_field(); ?>
                <button
                    type="submit"
                    class="inline-flex items-center px-3 py-1.5 bg-white border border-amber-300 rounded-md font-semibold text-xs text-amber-900 uppercase tracking-widest hover:bg-amber-50 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition"
                >
                    Cancel request
                </button>
            </form>
        </div>
    <?php else: ?>
        
        <form
            method="POST"
            action="<?php echo e(route('profile.deletion-request.store')); ?>"
            onsubmit="return confirm('Submit account-deletion request? A superadmin will review and confirm by email.');"
            class="space-y-4"
        >
            <?php echo csrf_field(); ?>

            <div>
                <label for="deletion-reason" class="block text-sm font-medium text-gray-700">
                    Reason <span class="text-xs text-gray-400">(optional)</span>
                </label>
                <textarea
                    id="deletion-reason"
                    name="reason"
                    rows="3"
                    maxlength="1000"
                    placeholder="e.g. I'm leaving the company, please remove my account."
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                ><?php echo e(old('reason')); ?></textarea>
                <p class="mt-1 text-xs text-gray-500">
                    Helps the superadmin verify the request. We never share this with anyone else.
                </p>
            </div>

            <button
                type="submit"
                class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition"
            >
                Request Account Deletion
            </button>
        </form>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</section>
<?php /**PATH C:\Users\USER\Documents\MONDAY.COM\Web Side Project\customer-portal\portal\resources\views/profile/partials/request-deletion-card.blade.php ENDPATH**/ ?>