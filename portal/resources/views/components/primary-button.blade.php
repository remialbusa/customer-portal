<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-5 py-2.5 bg-brand-cta border border-transparent rounded-lg font-semibold text-sm text-white shadow-glow hover:opacity-95 active:opacity-90 focus:outline-none focus:ring-2 focus:ring-brand-blue focus:ring-offset-2 transition ease-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed']) }}>
    {{ $slot }}
</button>
