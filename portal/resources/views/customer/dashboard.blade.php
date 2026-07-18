<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <p class="text-xs font-semibold tracking-widest uppercase text-base-content/50 mb-1">
                    {{ $user->account_name ? $user->account_name : 'Customer' }}
                </p>
                <h2 class="font-semibold text-2xl text-base-content leading-tight">
                    Hello, {{ explode(' ', $user->name)[0] }} 👋
                </h2>
                <p class="text-sm text-base-content/60 mt-1">
                    Track your service requests and start a new one when you need us.
                </p>
            </div>
            <a href="{{ route('tickets.create') }}" class="btn btn-primary gap-2 shadow-soft">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New service request
            </a>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-4xl mx-auto sm:px-4 lg:px-6 space-y-6">

            @if (session('status'))
                <x-ui.toast type="success" title="All set!">
                    {{ session('status') }}
                </x-ui.toast>
            @endif

            {{-- ───── Quick glance (4 stat cards) ───── --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <x-ui.card padding="p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-base-200 text-base-content/70 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-base-content/60 uppercase tracking-wider">Total</p>
                            <p class="text-2xl font-extrabold text-base-content leading-none mt-0.5">{{ $stats['total'] }}</p>
                        </div>
                    </div>
                </x-ui.card>

                <x-ui.card padding="p-4" tone="accent">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-info/10 text-info flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-info uppercase tracking-wider">Open</p>
                            <p class="text-2xl font-extrabold text-base-content leading-none mt-0.5">{{ $stats['open'] }}</p>
                        </div>
                    </div>
                </x-ui.card>

                <x-ui.card padding="p-4" tone="warning">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-warning/10 text-warning flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-warning uppercase tracking-wider">In progress</p>
                            <p class="text-2xl font-extrabold text-base-content leading-none mt-0.5">{{ $stats['in_progress'] }}</p>
                        </div>
                    </div>
                </x-ui.card>

                <x-ui.card padding="p-4" tone="success">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-secondary/10 text-secondary flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-secondary uppercase tracking-wider">Resolved</p>
                            <p class="text-2xl font-extrabold text-base-content leading-none mt-0.5">{{ $stats['resolved'] }}</p>
                        </div>
                    </div>
                </x-ui.card>
            </div>

            {{-- ───── Your service requests ───── --}}
            <x-ui.card
                title="Your service requests"
                subtitle="Tap any row to see the full timeline and updates."
                padding="p-0"
            >
                <x-slot:icon>
                    <span aria-hidden="true" class="w-7 h-7 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    </span>
                </x-slot:icon>
                <x-slot:actions>
                    <a href="{{ route('tickets.create') }}" class="btn btn-ghost btn-sm gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        New
                    </a>
                </x-slot:actions>

                @if(empty($tickets))
                    <div class="p-2">
                        <x-ui.empty-state
                            icon="📋"
                            title="No service requests yet"
                            body="When you submit a service request it will show up here so you can track progress and add updates."
                            cta="Start a service request"
                            :ctaRoute="'tickets.create'"
                        />
                    </div>
                @else
                    <ul role="list" class="divide-y divide-base-300/70">
                        @foreach($tickets as $t)
                            @php
                                $statusLower = strtolower((string) $t['status_text']);
                                $statusConfig = match(true) {
                                    str_contains($statusLower, 'new') || str_contains($statusLower, 'open')
                                        => ['class' => 'badge-info',    'dot' => 'bg-info'],
                                    str_contains($statusLower, 'progress')
                                        => ['class' => 'badge-warning', 'dot' => 'bg-warning'],
                                    str_contains($statusLower, 'awaiting')
                                        => ['class' => 'badge-accent',  'dot' => 'bg-accent'],
                                    str_contains($statusLower, 'resolved') || str_contains($statusLower, 'closed') || str_contains($statusLower, 'done') || str_contains($statusLower, 'complete')
                                        => ['class' => 'badge-success', 'dot' => 'bg-success'],
                                    default
                                        => ['class' => 'badge-ghost',   'dot' => 'bg-base-content/40'],
                                };
                            @endphp
                            <li>
                                <a href="{{ route('tickets.show', $t['id']) }}"
                                   class="block px-4 py-3.5 hover:bg-base-200/60 transition group">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="text-[11px] font-mono text-base-content/50">#{{ $t['id'] }}</span>
                                                <span class="badge {{ $statusConfig['class'] }} badge-sm gap-1 font-medium">
                                                    <span class="w-1.5 h-1.5 rounded-full {{ $statusConfig['dot'] }}"></span>
                                                    {{ $t['status_text'] ?? '—' }}
                                                </span>
                                            </div>
                                            <h3 class="text-sm font-semibold text-base-content truncate group-hover:text-primary transition">
                                                {{ $t['name'] }}
                                            </h3>
                                            <div class="flex flex-wrap items-center gap-x-3 gap-y-0.5 text-[11px] text-base-content/60 mt-1">
                                                @if(!empty($t['request_type_text']))
                                                    <span>{{ $t['request_type_text'] }}</span>
                                                @endif
                                                @if(!empty($t['updates_count']))
                                                    <span>{{ $t['updates_count'] }} update{{ $t['updates_count'] === 1 ? '' : 's' }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <svg class="w-4 h-4 text-base-content/40 group-hover:text-primary group-hover:translate-x-0.5 transition flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
