<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold tracking-widest uppercase text-base-content/50 mb-1">
                Executive
            </p>
            <h2 class="font-semibold text-2xl text-base-content leading-tight">
                KPI dashboard
            </h2>
            <p class="text-sm text-base-content/60 mt-1">
                Hero stats, MTTR, MTBF, and drill-down tiles will live here.
            </p>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-7xl mx-auto sm:px-4 lg:px-6 space-y-6">
            <x-ui.card padding="p-6">
                <x-slot:icon>
                    <span aria-hidden="true" class="w-7 h-7 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </span>
                </x-slot:icon>

                <p class="text-sm text-base-content/80">
                    Logged in as <span class="font-semibold">{{ $user->name }}</span>
                    &middot; Administrator
                </p>
                <p class="mt-3 text-sm text-base-content/60">
                    Hero stats, MTTR, MTBF, and drill-down tiles will be built here.
                </p>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
