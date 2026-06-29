<?php

use Livewire\Attributes\On;
use Livewire\Volt\Component;

?>

<div
    x-data="timeTracker({
        ticketId: <?php echo \Illuminate\Support\Js::from((int) $ticketId)->toHtml() ?>,
        active:   <?php echo \Illuminate\Support\Js::from($active ?: null)->toHtml() ?>,
        total:    <?php echo \Illuminate\Support\Js::from((int) $totalSeconds)->toHtml() ?>,
    })"
    x-init="
        init();
        // Keep Alpine in sync with Livewire's re-rendered values
        $watch('$wire.active', (v) => { active = v; recompute(); });
        $watch('$wire.totalSeconds', (v) => { total = Number(v || 0); recompute(); });
    "
    @time-tracker-state.window="onState($event.detail)"
    class="bg-white shadow sm:rounded-lg p-6"
>
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-base font-semibold text-gray-900">Time tracker</h3>
        <div class="text-xs text-gray-500 flex items-center gap-2">
            <template x-if="active">
                <span class="inline-flex items-center gap-1 text-amber-600">
                    <span class="h-2 w-2 rounded-full bg-amber-500 animate-pulse"></span>
                    <span x-text="active.status === 'open' ? 'Running' : 'Paused'"></span>
                </span>
            </template>
            <template x-if="!active">
                <span class="text-gray-400">No active timer</span>
            </template>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="border border-gray-200 rounded p-4">
            <div class="text-xs uppercase text-gray-500">Total on this ticket</div>
            <div class="text-2xl font-bold mt-1" x-text="formatTotal(total)">—</div>
        </div>
        <div class="border border-gray-200 rounded p-4">
            <div class="text-xs uppercase text-gray-500">Current session</div>
            <div class="text-2xl font-bold mt-1" x-text="formatElapsed(elapsedSeconds)">0m 00s</div>
            <div class="text-xs text-gray-500 mt-1" x-text="activeLabel"></div>
        </div>
    </div>

    <div class="mt-4 flex flex-wrap gap-2">
        <template x-if="!active">
            <div class="flex w-full gap-2">
                <input
                    type="text"
                    wire:model="note"
                    placeholder="What are you working on? (optional)"
                    maxlength="500"
                    class="flex-1 border-gray-300 rounded text-sm"
                />
                <button
                    type="button"
                    wire:click="start"
                    wire:loading.attr="disabled"
                    class="px-4 py-2 bg-indigo-600 text-white rounded text-sm hover:bg-indigo-700"
                >
                    Start timer
                </button>
            </div>
        </template>

        <template x-if="active && active.status === 'open'">
            <div class="flex w-full gap-2">
                <button
                    type="button"
                    wire:click="pause"
                    class="flex-1 px-4 py-2 bg-amber-500 text-white rounded text-sm hover:bg-amber-600"
                >
                    Pause
                </button>
                <button
                    type="button"
                    wire:click="stop"
                    wire:confirm="Stop the timer and log this session to Monday?"
                    class="flex-1 px-4 py-2 bg-red-600 text-white rounded text-sm hover:bg-red-700"
                >
                    Stop &amp; log
                </button>
            </div>
        </template>

        <template x-if="active && active.status === 'paused'">
            <div class="flex w-full gap-2">
                <button
                    type="button"
                    wire:click="resume"
                    class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded text-sm hover:bg-indigo-700"
                >
                    Resume
                </button>
                <button
                    type="button"
                    wire:click="stop"
                    wire:confirm="Stop the timer and log this session to Monday?"
                    class="flex-1 px-4 py-2 bg-red-600 text-white rounded text-sm hover:bg-red-700"
                >
                    Stop &amp; log
                </button>
            </div>
        </template>
    </div>
</div><?php /**PATH C:\Users\USER\Documents\MONDAY.COM\Web Side Project\customer-portal\portal\resources\views\livewire/time-tracker.blade.php ENDPATH**/ ?>