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
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            TSP Dashboard
        </h2>
     <?php $__env->endSlot(); ?>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            
            <div class="bg-white p-6 rounded shadow">
                <p class="text-sm text-gray-500">
                    Logged in as <span class="font-medium"><?php echo e($user->name); ?></span>
                    &middot; <span class="uppercase"><?php echo e($user->role); ?></span>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($user->team): ?> &middot; <?php echo e($user->team); ?> <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($user->region): ?> &middot; <?php echo e($user->region); ?> <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </p>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(empty($user->monday_id)): ?>
                    <p class="mt-2 text-sm text-amber-700 bg-amber-50 p-3 rounded">
                        Your account is not yet linked to a Monday.com person.
                        Tickets won't show up until an admin sets your <code>monday_id</code>.
                    </p>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white p-5 rounded shadow">
                    <div class="text-xs uppercase text-gray-500">Assigned to me</div>
                    <div class="text-3xl font-bold mt-1"><?php echo e($assignedCount); ?></div>
                </div>
                <div class="bg-white p-5 rounded shadow">
                    <div class="text-xs uppercase text-gray-500">Open</div>
                    <div class="text-3xl font-bold mt-1 text-blue-600"><?php echo e($openCount); ?></div>
                </div>
                <div class="bg-white p-5 rounded shadow">
                    <div class="text-xs uppercase text-gray-500">In progress</div>
                    <div class="text-3xl font-bold mt-1 text-amber-600"><?php echo e($inProgressCount); ?></div>
                </div>
            </div>

            
            <div class="bg-white rounded shadow">
                <div class="px-6 py-4 border-b flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">My Tickets</h3>
                    <span class="text-xs text-gray-500">From Monday.com &middot; cached 30s</span>
                </div>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(empty($tickets)): ?>
                    <div class="p-6 text-gray-500 text-sm">
                        No tickets assigned to you yet. Once tickets are assigned in
                        Monday.com, they'll show up here within 30 seconds.
                    </div>
                <?php else: ?>
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                            <tr>
                                <th class="px-6 py-3 text-left">Ticket</th>
                                <th class="px-6 py-3 text-left">Status</th>
                                <th class="px-6 py-3 text-left">Priority</th>
                                <th class="px-6 py-3 text-left">Request Type</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $tickets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td class="px-6 py-3 font-medium text-gray-900">
                                        #<?php echo e($t['id']); ?> &mdash; <?php echo e($t['name']); ?>

                                    </td>
                                    <td class="px-6 py-3">
                                        <span class="px-2 py-1 rounded text-xs
                                            <?php if(str_contains(strtolower($t['status_text'] ?? ''), 'progress')): ?> bg-amber-100 text-amber-800
                                            <?php elseif(str_contains(strtolower($t['status_text'] ?? ''), 'new')): ?> bg-blue-100 text-blue-800
                                            <?php elseif(str_contains(strtolower($t['status_text'] ?? ''), 'hold')): ?> bg-gray-200 text-gray-800
                                            <?php elseif(in_array(strtolower($t['status_text'] ?? ''), ['resolved','closed'])): ?> bg-green-100 text-green-800
                                            <?php else: ?> bg-gray-100 text-gray-800 <?php endif; ?>">
                                            <?php echo e($t['status_text'] ?? '—'); ?>

                                        </span>
                                    </td>
                                    <td class="px-6 py-3"><?php echo e($t['priority_text'] ?? '—'); ?></td>
                                    <td class="px-6 py-3"><?php echo e($t['request_type_text'] ?? '—'); ?></td>
                                    <td class="px-6 py-3 text-right">
                                        <a href="<?php echo e(route('tsp.tickets.show', $t['id'])); ?>"
                                           class="text-indigo-600 hover:underline text-xs">
                                            Open
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </tbody>
                    </table>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

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
<?php /**PATH C:\Users\USER\Documents\MONDAY.COM\Web Side Project\customer-portal\portal\resources\views/tsp/dashboard.blade.php ENDPATH**/ ?>