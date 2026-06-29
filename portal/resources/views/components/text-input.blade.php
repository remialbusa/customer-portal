@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'block w-full rounded-lg border-brand-mist bg-white text-brand-navy placeholder:text-brand-slate/50 focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/30 focus:ring-offset-0 shadow-sm transition']) }}>
