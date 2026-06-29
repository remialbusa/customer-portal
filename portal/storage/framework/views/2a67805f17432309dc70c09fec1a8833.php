<?php if (isset($component)) { $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54 = $attributes; } ?>
<?php $component = App\View\Components\AppLayout::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\AppLayout::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
     <?php $__env->slot('header', null, []); ?> 
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Ticket #<?php echo e($ticket['id']); ?> &mdash; <?php echo e($ticket['name']); ?>

            </h2>
            <a href="<?php echo e(route('tsp.dashboard')); ?>" class="text-sm text-indigo-600 hover:underline">
                &larr; Back to dashboard
            </a>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <div class="lg:col-span-1 bg-white shadow sm:rounded-lg p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Details</h3>
                    <dl class="space-y-3 text-sm">
                        <?php
                            $status   = $ticket['column_values']['status95']['text']        ?? null;
                            $priority = $ticket['column_values']['priority']['text']         ?? null;
                            $rtype    = $ticket['column_values']['request_type']['text']    ?? null;
                            $account  = $ticket['column_values']['lookup_mm4f1f6y']['display_value'] ?? null;
                            $branch   = $ticket['column_values']['lookup_mm4fj9gp']['display_value'] ?? null;
                            $email    = $ticket['column_values']['email']['text']           ?? null;
                            $created  = $ticket['column_values']['date']['text']             ?? null;
                            $desc     = $ticket['column_values']['long_text7']['text']       ?? null;
                        ?>

                        <div>
                            <dt class="text-xs text-gray-500 uppercase">Status</dt>
                            <dd class="mt-1 text-gray-900"><?php echo e($status ?? '—'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 uppercase">Priority</dt>
                            <dd class="mt-1 text-gray-900"><?php echo e($priority ?? '—'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 uppercase">Type</dt>
                            <dd class="mt-1 text-gray-900"><?php echo e($rtype ?? '—'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 uppercase">Account</dt>
                            <dd class="mt-1 text-gray-900"><?php echo e($account ?? '—'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 uppercase">Branch</dt>
                            <dd class="mt-1 text-gray-900"><?php echo e($branch ?? '—'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 uppercase">Customer email</dt>
                            <dd class="mt-1 text-gray-900"><?php echo e($email ?? '—'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 uppercase">Submitted</dt>
                            <dd class="mt-1 text-gray-900"><?php echo e($created ?? '—'); ?></dd>
                        </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($desc): ?>
                            <div class="pt-3 border-t border-gray-100">
                                <dt class="text-xs text-gray-500 uppercase">Description</dt>
                                <dd class="mt-1 text-gray-900 whitespace-pre-wrap"><?php echo e($desc); ?></dd>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </dl>
                </div>

                
                <div class="lg:col-span-2">
                    <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('chat-panel', ['ticketId' => $ticket['id'],'currentUserName' => $user->name,'currentUserRole' => $user->role,'messages' => $messages]);

$__key = null;

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-2672644522-0', $__key);

$__html = app('livewire')->mount($__name, $__params, $__key);

echo $__html;

unset($__html);
unset($__key);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
                </div>
            </div>

            
            <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('time-tracker', ['ticketId' => $ticket['id'],'currentUserName' => $user->name,'currentUserRole' => $user->role,'active' => $timeActive,'totalSeconds' => $timeTotal]);

$__key = null;

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-2672644522-1', $__key);

$__html = app('livewire')->mount($__name, $__params, $__key);

echo $__html;

unset($__html);
unset($__key);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>

            
            <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('internal-notes-panel', ['ticketId' => $ticket['id'],'currentUserName' => $user->name,'currentUserRole' => $user->role,'notes' => $notes]);

$__key = null;

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-2672644522-2', $__key);

$__html = app('livewire')->mount($__name, $__params, $__key);

echo $__html;

unset($__html);
unset($__key);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>

            
            <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('tsp.tickets.create-service-report', ['ticketNumber' => $ticket['id']]);

$__key = null;

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-2672644522-3', $__key);

$__html = app('livewire')->mount($__name, $__params, $__key);

echo $__html;

unset($__html);
unset($__key);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>

        </div>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $attributes = $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $component = $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>

<?php /**PATH C:\Users\USER\Documents\MONDAY.COM\Web Side Project\customer-portal\portal\resources\views/tsp/ticket-show.blade.php ENDPATH**/ ?>