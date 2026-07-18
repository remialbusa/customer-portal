@props([
    'icon'        => '📭',
    'title'       => 'Nothing here yet',
    'body'        => null,
    'cta'         => null,
    'ctaHref'     => null,
    'ctaRoute'    => null,
    'secondaryCta' => null,
    'secondaryHref' => null,
])

@php
    $primaryHref = $ctaHref ?? ($ctaRoute ? route($ctaRoute) : null);
    $secondaryHref = $secondaryHref ?? null;
@endphp

<div {{ $attributes->merge(['class' => 'card bg-base-100 border border-dashed border-base-300 rounded-2xl p-10 text-center']) }}>
    <div class="mx-auto w-16 h-16 rounded-full bg-base-200 flex items-center justify-center text-3xl mb-4" aria-hidden="true">
        {{ $icon }}
    </div>
    <h3 class="text-lg font-semibold text-base-content">{{ $title }}</h3>
    @if ($body)
        <p class="mt-2 text-sm text-base-content/60 max-w-md mx-auto">{{ $body }}</p>
    @endif

    @if ($slot->isNotEmpty())
        <div class="mt-4 text-sm text-base-content/80">{{ $slot }}</div>
    @endif

    @if ($primaryHref || $secondaryHref)
        <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
            @if ($primaryHref)
                <a href="{{ $primaryHref }}" class="btn btn-primary">
                    {{ $cta ?? 'Get started' }}
                </a>
            @endif
            @if ($secondaryHref)
                <a href="{{ $secondaryHref }}" class="btn btn-ghost">
                    {{ $secondaryCta }}
                </a>
            @endif
        </div>
    @endif
</div>
