@props([
    'title'       => null,
    'subtitle'    => null,
    'icon'        => null,
    'padding'     => 'p-6',
    'tone'        => 'default',
    'interactive' => false,
])

@php
    $toneRing = match($tone) {
        'accent'  => 'ring-1 ring-accent/20',
        'success' => 'ring-1 ring-secondary/20',
        'warning' => 'ring-1 ring-warning/30',
        'danger'  => 'ring-1 ring-error/20',
        default   => '',
    };
    $interactiveClass = $interactive
        ? 'transition hover:-translate-y-0.5 hover:shadow-glow cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/40'
        : '';
    // When the card itself has no padding (p-0), the header needs its
    // own horizontal + top padding so the icon doesn't touch the card's
    // top-left border. When the card already has padding, let the
    // natural card padding do the spacing.
    $headerPad = trim($padding) === 'p-0' ? 'px-5 pt-5' : '';
@endphp

<section
    {{ $attributes->merge(['class' => "card bg-base-100 border border-base-300/70 shadow-soft rounded-2xl $padding $toneRing $interactiveClass"]) }}
>
    @if ($title || $icon || $subtitle || isset($actions) || isset($iconSlot) && $iconSlot->isNotEmpty())
        <header class="flex items-center gap-3 {{ $title || $subtitle ? 'mb-4' : '' }} {{ $headerPad }}">
            @if (isset($iconSlot) && $iconSlot->isNotEmpty())
                <span class="shrink-0">{{ $iconSlot }}</span>
            @elseif ($icon)
                <span aria-hidden="true" class="text-2xl leading-none mt-0.5">{{ $icon }}</span>
            @endif
            <div class="min-w-0 flex-1">
                @if ($title)
                    <h3 class="text-base font-semibold text-base-content leading-tight">{{ $title }}</h3>
                @endif
                @if ($subtitle)
                    <p class="text-sm text-base-content/60 mt-0.5">{{ $subtitle }}</p>
                @endif
            </div>
            @if (isset($actions))
                <div class="shrink-0 flex items-center gap-2">{{ $actions }}</div>
            @endif
        </header>
    @endif

    <div>
        {{ $slot }}
    </div>

    @if (isset($footer))
        <footer class="mt-4 pt-4 border-t border-base-300/60">
            {{ $footer }}
        </footer>
    @endif
</section>
