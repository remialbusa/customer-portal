@props(['active'])

@php
// Brand-aligned nav link. Active state uses the medical green underline
// (secondary) instead of the old indigo to keep the nav on-tone.
$classes = ($active ?? false)
            ? 'inline-flex items-center px-3 py-2 rounded-md text-sm font-semibold text-secondary border-b-2 border-secondary transition'
            : 'inline-flex items-center px-3 py-2 rounded-md text-sm font-medium text-base-content/70 hover:text-base-content hover:bg-base-200 border-b-2 border-transparent transition';
@endphp

<a {{ $attributes->merge(['class' => $classes, 'wire:navigate' => true]) }}>
    {{ $slot }}
</a>
