@props([
    'type'    => 'info',   // info | success | warning | error
    'title'   => null,
    'dismiss' => true,
])

@php
    $icon = match($type) {
        'success' => '✓',
        'warning' => '!',
        'error'   => '×',
        default   => 'i',
    };
    $alertClass = match($type) {
        'success' => 'alert-success',
        'warning' => 'alert-warning',
        'error'   => 'alert-error',
        default   => 'alert-info',
    };
@endphp

<div
    {{ $attributes->merge(['class' => "alert $alertClass shadow-soft rounded-xl"]) }}
    role="status"
    @if ($type === 'error') role="alert" @endif
>
    <span class="w-7 h-7 rounded-full bg-white/30 flex items-center justify-center font-bold" aria-hidden="true">{{ $icon }}</span>
    <div class="flex-1">
        @if ($title)
            <h4 class="font-semibold">{{ $title }}</h4>
        @endif
        <div class="text-sm">{{ $slot }}</div>
    </div>
    @if ($dismiss)
        <button type="button" class="btn btn-ghost btn-sm btn-circle" aria-label="Dismiss" onclick="this.closest('.alert').remove()">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    @endif
</div>
