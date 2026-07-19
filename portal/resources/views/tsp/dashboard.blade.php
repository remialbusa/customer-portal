<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <p class="text-xs font-semibold tracking-widest uppercase text-base-content/50 mb-1">
                    Service technician
                </p>
                <h2 class="font-semibold text-2xl text-base-content leading-tight">
                    Welcome back, {{ $user->name }}
                </h2>
                <p class="text-sm text-base-content/60 mt-1">
                    @if($user->team) {{ $user->team }} @endif
                    @if($user->region) &middot; {{ $user->region }} @endif
                </p>
            </div>
            <span class="badge badge-primary badge-lg gap-1.5 font-medium self-start sm:self-auto">
                <span class="w-1.5 h-1.5 rounded-full bg-primary-content"></span>
                {{ strtoupper($user->role) }}
            </span>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-4xl mx-auto sm:px-4 lg:px-6 space-y-4">

            @if (session('status'))
                <x-ui.toast type="success" title="All set!">
                    {{ session('status') }}
                </x-ui.toast>
            @endif

            @if ($errors->any())
                <x-ui.toast type="error" title="Something went wrong">
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-ui.toast>
            @endif

            @if(empty($user->monday_id))
                <div role="alert" class="alert alert-warning shadow-sm">
                    <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    <div>
                        <h3 class="font-semibold">Account not yet linked to Monday</h3>
                        <div class="text-xs mt-0.5">
                            Your account is missing a <code class="px-1 py-0.5 rounded bg-warning/20 font-mono text-[11px]">monday_id</code>.
                            Tickets won't show up until an admin sets it.
                        </div>
                    </div>
                </div>
            @endif

            {{-- ───── Stats cards (2x2 grid, mirrors customer side) ───── --}}
            <div class="grid grid-cols-2 gap-3">
                {{-- Total --}}
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

                {{-- Open --}}
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

                {{-- In Progress --}}
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

                {{-- Resolved --}}
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

            {{-- ───── Pending-sync card (TSP-only) ─────
                 Surfaces service reports this TSP has filed that are
                 still waiting to drain to Monday.com. Hidden when zero. --}}
            @if($stats['pending_sync'] > 0)
                <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-warning/10 border border-warning/30">
                    <div class="w-9 h-9 rounded-full bg-warning/20 text-warning flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-base-content">
                            {{ $stats['pending_sync'] }} service report{{ $stats['pending_sync'] === 1 ? '' : 's' }} pending sync
                        </p>
                        <p class="text-[11px] text-base-content/70">Waiting to mirror to Monday.com — these retry automatically when online.</p>
                    </div>
                </div>
            @endif

            {{-- ───── Available tickets (regional pool) ───── --}}
            @if(!empty($unclaimedTickets))
                <x-ui.card
                    title="Available tickets in your region"
                    subtitle="Claim a ticket to add it to your queue — it will also appear for other TSPs until claimed."
                    padding="p-0"
                >
                    <x-slot:icon>
                        <span aria-hidden="true" class="w-7 h-7 rounded-lg bg-warning/10 text-warning flex items-center justify-center shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                        </span>
                    </x-slot:icon>

                    <ul role="list" class="divide-y divide-base-300/70">
                        @foreach($unclaimedTickets as $t)
                            @php
                                $statusLower = strtolower((string) $t['status_text']);
                                $statusConfig = match(true) {
                                    str_contains($statusLower, 'new') || str_contains($statusLower, 'open')
                                        => ['class' => 'badge-info',    'dot' => 'bg-info'],
                                    str_contains($statusLower, 'progress')
                                        => ['class' => 'badge-warning', 'dot' => 'bg-warning'],
                                    default
                                        => ['class' => 'badge-ghost',   'dot' => 'bg-base-content/40'],
                                };
                                $brand = $t['item']['column_values']['text_mm5apcrc']['text'] ?? null;
                                $model = $t['item']['column_values']['text_mm5am2kf']['text'] ?? null;
                            @endphp
                            <li x-data="{ claimOpen: false }">
                                <div class="flex items-center gap-3 px-4 py-3.5 hover:bg-base-200/60 transition group">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="text-[11px] font-mono text-base-content/50">#{{ $t['id'] }}</span>
                                            <span class="badge {{ $statusConfig['class'] }} badge-sm gap-1 font-medium">
                                                <span class="w-1.5 h-1.5 rounded-full {{ $statusConfig['dot'] }}"></span>
                                                {{ $t['status_text'] ?? '—' }}
                                            </span>
                                            @if(!empty($t['customer_region']))
                                                <span class="badge badge-outline badge-sm text-[10px]">{{ $t['customer_region'] }}</span>
                                            @endif
                                        </div>
                                        <h3 class="text-sm font-semibold text-base-content truncate">
                                            {{ $t['subject_text'] ?: $t['name'] }}
                                        </h3>
                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-0.5 text-[11px] text-base-content/60 mt-1">
                                            @if($brand || $model)
                                                <span class="inline-flex items-center gap-1">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                                                    {{ trim(($brand ?? '') . ' ' . (($brand && $model) ? '· ' : '') . ($model ?? '')) }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <button type="button"
                                            x-on:click="claimOpen = true"
                                            class="btn btn-sm btn-primary gap-1 flex-shrink-0">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                        Claim
                                    </button>
                                </div>

                                {{-- Claim confirmation modal --}}
                                <div x-show="claimOpen"
                                     x-transition:enter="ease-out duration-200"
                                     x-transition:enter-start="opacity-0"
                                     x-transition:enter-end="opacity-100"
                                     x-transition:leave="ease-in duration-150"
                                     x-transition:leave-start="opacity-100"
                                     x-transition:leave-end="opacity-0"
                                     class="fixed inset-0 z-50 flex items-center justify-center p-4"
                                     x-cloak>
                                    {{-- Backdrop --}}
                                    <div class="absolute inset-0 bg-black/50" x-on:click="claimOpen = false"></div>

                                    {{-- Dialog --}}
                                    <div x-show="claimOpen"
                                         x-transition:enter="ease-out duration-200"
                                         x-transition:enter-start="opacity-0 scale-95"
                                         x-transition:enter-end="opacity-100 scale-100"
                                         x-transition:leave="ease-in duration-150"
                                         x-transition:leave-start="opacity-100 scale-100"
                                         x-transition:leave-end="opacity-0 scale-95"
                                         class="relative bg-base-100 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
                                        {{-- Header --}}
                                        <div class="px-6 pt-6 pb-5">
                                            <div class="flex items-start gap-3">
                                                <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                                </div>
                                                <div class="min-w-0">
                                                    <h3 class="text-lg font-bold text-base-content">Claim this ticket?</h3>
                                                    <p class="text-sm text-base-content/60 mt-0.5">It will be assigned to you and removed from the regional pool.</p>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Ticket summary --}}
                                        <div class="mx-6 mb-5 p-4 bg-base-200/60 rounded-xl space-y-2">
                                            <div class="flex items-center gap-2">
                                                <span class="text-[11px] font-mono text-base-content/50">#{{ $t['id'] }}</span>
                                                <span class="badge {{ $statusConfig['class'] }} badge-sm gap-1 font-medium">
                                                    <span class="w-1.5 h-1.5 rounded-full {{ $statusConfig['dot'] }}"></span>
                                                    {{ $t['status_text'] ?? '—' }}
                                                </span>
                                                @if(!empty($t['customer_region']))
                                                    <span class="badge badge-outline badge-sm text-[10px]">{{ $t['customer_region'] }}</span>
                                                @endif
                                            </div>
                                            <p class="text-sm font-semibold text-base-content leading-snug">
                                                {{ $t['subject_text'] ?: $t['name'] }}
                                            </p>
                                            @if($brand || $model)
                                                <div class="flex items-center gap-1 text-xs text-base-content/60">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                                                    {{ trim(($brand ?? '') . ' ' . (($brand && $model) ? '· ' : '') . ($model ?? '')) }}
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Actions --}}
                                        <div class="px-6 pt-6 pb-10 flex justify-end gap-2">
                                            <button type="button"
                                                    x-on:click="claimOpen = false"
                                                    class="btn btn-ghost btn-sm">
                                                Cancel
                                            </button>
                                            <form method="POST" action="{{ route('tsp.tickets.claim', $t['id']) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-primary btn-sm gap-1.5">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                    Yes, claim ticket
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </x-ui.card>
            @endif

            {{-- ───── My Tickets card ───── --}}
            <x-ui.card
                title="My tickets"
                subtitle="From Monday.com · cached 30s"
                padding="p-0"
            >
                <x-slot:icon>
                    <span aria-hidden="true" class="w-7 h-7 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </span>
                </x-slot:icon>

                @if(empty($tickets))
                    <div class="p-2">
                        <x-ui.empty-state
                            icon="📋"
                            title="No tickets claimed yet"
                            body="Check the available tickets pool above to claim one, or wait for new tickets to come in from your region."
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

                                // Brand/model line for the TSP — they
                                // need this at-a-glance since they may
                                // have many open tickets across accounts.
                                $brand = $t['column_values']['text_mm5apcrc']['text'] ?? null;
                                $model = $t['column_values']['text_mm5am2kf']['text'] ?? null;
                            @endphp
                            <li>
                                <a href="{{ route('tsp.tickets.show', $t['id']) }}"
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
                                                {{ $t['subject_text'] ?: $t['name'] }}
                                            </h3>
                                            <div class="flex flex-wrap items-center gap-x-3 gap-y-0.5 text-[11px] text-base-content/60 mt-1">
                                                @if($brand || $model)
                                                    <span class="inline-flex items-center gap-1">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                                                        {{ trim(($brand ?? '') . ' ' . (($brand && $model) ? '· ' : '') . ($model ?? '')) }}
                                                    </span>
                                                @endif
                                                @if(!empty($t['updates_count']))
                                                    <span class="inline-flex items-center gap-1">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                                        {{ $t['updates_count'] }} update{{ $t['updates_count'] === 1 ? '' : 's' }}
                                                    </span>
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
