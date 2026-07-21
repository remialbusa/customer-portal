@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-secondary text-start text-base font-semibold text-secondary bg-secondary/10 transition'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-base-content/80 hover:text-base-content hover:bg-base-200 transition';
@endphp

<a {{ $attributes->merge(['class' => $classes, 'wire:navigate' => true]) }}>
    {{ $slot }}
</a>
