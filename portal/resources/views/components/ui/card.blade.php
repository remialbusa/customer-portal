@props([
    'padding' => 'p-6',
    'shadow' => 'shadow-sm',
    'rounded' => 'rounded-lg',
    'border' => true,
    'hover' => false,
])

@php
    $classes = 'bg-white ' . $rounded . ' ' . $shadow;
    if ($border) {
        $classes .= ' border border-gray-200';
    }
    if ($hover) {
        $classes .= ' hover:shadow-md transition-shadow duration-200';
    }
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    @if(isset($header))
        <div class="px-6 py-4 border-b border-gray-200">
            {{ $header }}
        </div>
    @endif

    <div class="{{ $padding }}">
        {{ $slot }}
    </div>

    @if(isset($footer))
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            {{ $footer }}
        </div>
    @endif
</div>
