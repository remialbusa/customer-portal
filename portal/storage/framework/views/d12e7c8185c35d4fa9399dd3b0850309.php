<?php

use Livewire\Attributes\On;
use Livewire\Volt\Component;

?>

<div
    x-data="chatPanel({
        ticketId: <?php echo \Illuminate\Support\Js::from($ticketId)->toHtml() ?>,
        currentUserName: <?php echo \Illuminate\Support\Js::from($currentUserName)->toHtml() ?>,
        currentUserRole: <?php echo \Illuminate\Support\Js::from($currentUserRole)->toHtml() ?>,
    })"
    x-init="init()"
    class="bg-white shadow sm:rounded-lg flex flex-col h-[640px]"
>
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h3 class="text-base font-medium text-gray-900">
            Chat with <?php echo e($currentUserRole === 'customer' ? 'our support team' : 'the customer'); ?>

        </h3>
        <span class="text-xs text-gray-400" x-show="connecting">Connecting…</span>
        <span class="text-xs text-green-500" x-show="! connecting && connected" x-cloak>● Live</span>
    </div>

    <div
        id="chat-log-<?php echo e($ticketId); ?>"
        class="flex-1 overflow-y-auto px-6 py-4 space-y-3"
    >
        <?php echo e($slot ?? ''); ?>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($messages)): ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $messages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $msg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="flex <?php echo e($msg['mine'] ? 'justify-end' : 'justify-start'); ?>">
                    <div class="max-w-[80%] rounded-lg px-4 py-2 text-sm
                                <?php echo e($msg['mine'] ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-900'); ?>">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! $msg['mine']): ?>
                            <div class="text-xs font-semibold mb-1 opacity-70">
                                <?php echo e($msg['sender_name']); ?>

                                <span class="ml-1 px-1.5 py-0.5 rounded bg-white/60 text-[10px] uppercase tracking-wider">
                                    <?php echo e($msg['sender_role']); ?>

                                </span>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <div class="whitespace-pre-wrap break-words"><?php echo e($msg['body']); ?></div>
                        <div class="text-[10px] mt-1 <?php echo e($msg['mine'] ? 'text-indigo-100' : 'text-gray-400'); ?>">
                            <?php
                                $ts = $msg['created_at'] ?? null;
                                echo $ts ? \Carbon\Carbon::parse($ts)->format('M j, g:i A') : '—';
                            ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="text-center text-sm text-gray-400 mt-12">
                    No messages yet — say hello 👋
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

    <form wire:submit.prevent="send" class="border-t border-gray-200 px-4 py-3 flex gap-2">
        <input
            type="text"
            wire:model="body"
            placeholder="Type a message…"
            maxlength="2000"
            autocomplete="off"
            class="flex-1 rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
        >
        <button
            type="submit"
            wire:loading.attr="disabled"
            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-xs font-semibold uppercase tracking-widest rounded-md hover:bg-indigo-700 disabled:opacity-50"
        >
            Send
        </button>
    </form>
</div><?php /**PATH C:\Users\USER\Documents\MONDAY.COM\Web Side Project\customer-portal\portal\resources\views\livewire/chat-panel.blade.php ENDPATH**/ ?>