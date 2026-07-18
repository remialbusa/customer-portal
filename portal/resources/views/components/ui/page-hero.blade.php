@props([
    'eyebrow' => null,
    'title'   => null,
    'body'    => null,
])

<section {{ $attributes->merge(['class' => 'rounded-2xl bg-gradient-to-br from-primary via-primary to-primary/85 text-primary-content shadow-soft overflow-hidden relative']) }}>
    <div class="absolute inset-0 opacity-20 pointer-events-none" aria-hidden="true">
        <svg class="w-full h-full" preserveAspectRatio="none" viewBox="0 0 800 200" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="dots" x="0" y="0" width="24" height="24" patternUnits="userSpaceOnUse">
                    <circle cx="2" cy="2" r="1" fill="currentColor" />
                </pattern>
            </defs>
            <rect width="800" height="200" fill="url(#dots)" />
        </svg>
    </div>

    <div class="relative px-6 py-10 sm:px-10 sm:py-14 max-w-4xl">
        @if ($eyebrow)
            <p class="text-xs font-semibold tracking-widest uppercase text-secondary-content/80 mb-3">
                {{ $eyebrow }}
            </p>
        @endif
        @if ($title)
            <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight">{{ $title }}</h1>
        @endif
        @if ($body)
            <p class="mt-3 text-primary-content/80 text-base sm:text-lg max-w-2xl">
                {{ $body }}
            </p>
        @endif
        @if ($slot->isNotEmpty())
            <div class="mt-6 flex flex-wrap items-center gap-3">
                {{ $slot }}
            </div>
        @endif
    </div>
</section>
