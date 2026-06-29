@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'mb-4 rounded-lg border border-brand-green-2/30 bg-brand-green-2/10 px-4 py-3 text-sm font-medium text-brand-green']) }}>
        {{ $status }}
    </div>
@endif
