@props([
    'icon' => null,
    'title',
    'description' => null,
    'action' => null,
    'actionLabel' => 'Get Started',
    'actionRoute' => null,
])

<div class="text-center py-12">
    @if($icon)
        <div class="mx-auto w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                {!! $icon !!}
            </svg>
        </div>
    @endif

    <h3 class="mt-2 text-sm font-semibold text-gray-900">{{ $title }}</h3>

    @if($description)
        <p class="mt-1 text-sm text-gray-500">{{ $description }}</p>
    @endif

    @if($action || $actionRoute)
        <div class="mt-6">
            @if($actionRoute)
                <a href="{{ $actionRoute }}"
                   class="inline-flex items-center px-4 py-2 bg-brand-blue border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-brand-blue-3 focus:bg-brand-blue-3 active:bg-brand-blue-4 focus:outline-none focus:ring-2 focus:ring-brand-blue focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ $actionLabel }}
                </a>
            @else
                {{ $action }}
            @endif
        </div>
    @endif
</div>
