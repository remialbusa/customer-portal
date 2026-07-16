@props([
    'title',
    'value',
    'icon' => null,
    'color' => 'blue',
    'trend' => null,
    'trendUp' => true,
])

@php
    $colors = [
        'blue' => 'text-blue-600 bg-blue-50',
        'green' => 'text-green-600 bg-green-50',
        'yellow' => 'text-yellow-600 bg-yellow-50',
        'red' => 'text-red-600 bg-red-50',
        'purple' => 'text-purple-600 bg-purple-50',
        'indigo' => 'text-indigo-600 bg-indigo-50',
    ];

    $iconColor = $colors[$color] ?? $colors['blue'];
@endphp

<div class="bg-white rounded-lg border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
    <div class="flex items-center justify-between">
        <div class="flex-1">
            <p class="text-sm font-medium text-gray-600">{{ $title }}</p>
            <p class="mt-2 text-3xl font-bold text-gray-900">{{ $value }}</p>

            @if($trend)
                <div class="mt-2 flex items-center text-sm">
                    @if($trendUp)
                        <svg class="w-4 h-4 text-green-500 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                    @else
                        <svg class="w-4 h-4 text-red-500 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    @endif
                    <span class="{{ $trendUp ? 'text-green-600' : 'text-red-600' }}">{{ $trend }}</span>
                </div>
            @endif
        </div>

        @if($icon)
            <div class="flex-shrink-0 w-12 h-12 rounded-lg {{ $iconColor }} flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    {!! $icon !!}
                </svg>
            </div>
        @endif
    </div>
</div>
