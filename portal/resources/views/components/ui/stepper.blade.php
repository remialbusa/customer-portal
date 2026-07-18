@props([
    'steps'   => [],   // ['What\'s wrong', 'Contact info', 'Review']
    'current' => 1,    // 1-based
])

@php
    $total = max(count($steps), 1);
    $current = max(1, min($current, $total));
@endphp

<ol {{ $attributes->merge(['class' => 'flex items-center w-full']) }} role="list">
    @foreach ($steps as $i => $label)
        @php
            $stepNumber = $i + 1;
            $isCurrent = $stepNumber === $current;
            $isComplete = $stepNumber < $current;
            $isLast = $stepNumber === $total;
        @endphp
        <li class="flex items-center {{ $isLast ? 'flex-1' : 'flex-1' }}">
            <div class="flex flex-col items-center me-2">
                <span @class([
                    'w-9 h-9 rounded-full flex items-center justify-center text-sm font-semibold border-2 transition',
                    'bg-primary border-primary text-primary-content'      => $isCurrent,
                    'bg-secondary border-secondary text-secondary-content' => $isComplete,
                    'bg-base-100 border-base-300 text-base-content/50'    => ! $isCurrent && ! $isComplete,
                ])>
                    @if ($isComplete)
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                            <path fill-rule="evenodd" d="M16.704 5.296a1 1 0 010 1.408l-7.997 8a1 1 0 01-1.408 0l-3.999-4a1 1 0 011.408-1.408L8 12.59l7.296-7.294a1 1 0 011.408 0z" clip-rule="evenodd" />
                        </svg>
                    @else
                        {{ $stepNumber }}
                    @endif
                </span>
            </div>
            <div class="flex-1">
                <p @class([
                    'text-sm font-medium leading-tight',
                    'text-primary'         => $isCurrent,
                    'text-base-content'    => $isComplete,
                    'text-base-content/60' => ! $isCurrent && ! $isComplete,
                ])>
                    {{ $label }}
                </p>
            </div>

            @if (! $isLast)
                <div @class([
                    'flex-1 h-0.5 mx-2 rounded',
                    'bg-secondary'         => $isComplete,
                    'bg-base-300'          => ! $isComplete,
                ])></div>
            @endif
        </li>
    @endforeach
</ol>
