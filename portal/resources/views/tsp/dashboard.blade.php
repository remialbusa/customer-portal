<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    TSP Dashboard
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    Welcome back, {{ $user->name }}
                    @if($user->team) — {{ $user->team }} @endif
                    @if($user->region) — {{ $user->region }} @endif
                </p>
            </div>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-indigo-50 text-indigo-700 uppercase tracking-wider">
                <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                {{ strtoupper($user->role) }}
            </span>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-4 lg:px-6 space-y-4">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm flex items-start gap-2">
                    <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if(empty($user->monday_id))
                <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-lg px-4 py-3 text-sm flex items-start gap-2">
                    <svg class="w-4 h-4 text-amber-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    <span>Your account is not yet linked to a Monday.com person. Tickets won't show up until an admin sets your <code class="px-1 py-0.5 rounded bg-amber-100 text-amber-900 font-mono text-[11px]">monday_id</code>.</span>
                </div>
            @endif

            {{-- ───── Stats cards (2x2 grid, mirrors customer side) ───── --}}
            <div class="grid grid-cols-2 gap-3">
                {{-- Total --}}
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-3 flex flex-col items-start">
                    <div class="flex items-center gap-1.5 mb-1.5">
                        <div class="w-6 h-6 rounded bg-gray-100 text-gray-500 flex items-center justify-center">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        </div>
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider">Total</span>
                    </div>
                    <p class="text-2xl font-extrabold text-gray-800 leading-none">{{ $stats['total'] }}</p>
                </div>

                {{-- Open --}}
                <div class="bg-blue-50 rounded-lg border border-blue-200 shadow-sm p-3 flex flex-col items-start">
                    <div class="flex items-center gap-1.5 mb-1.5">
                        <div class="w-6 h-6 rounded bg-blue-100 text-blue-600 flex items-center justify-center">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <span class="text-[10px] font-semibold text-blue-600 uppercase tracking-wider">Open</span>
                    </div>
                    <p class="text-2xl font-extrabold text-blue-700 leading-none">{{ $stats['open'] }}</p>
                </div>

                {{-- In Progress --}}
                <div class="bg-amber-50 rounded-lg border border-amber-200 shadow-sm p-3 flex flex-col items-start">
                    <div class="flex items-center gap-1.5 mb-1.5">
                        <div class="w-6 h-6 rounded bg-amber-100 text-amber-600 flex items-center justify-center">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                        <span class="text-[10px] font-semibold text-amber-600 uppercase tracking-wider">In Progress</span>
                    </div>
                    <p class="text-2xl font-extrabold text-amber-700 leading-none">{{ $stats['in_progress'] }}</p>
                </div>

                {{-- Resolved --}}
                <div class="bg-green-50 rounded-lg border border-green-200 shadow-sm p-3 flex flex-col items-start">
                    <div class="flex items-center gap-1.5 mb-1.5">
                        <div class="w-6 h-6 rounded bg-green-100 text-green-600 flex items-center justify-center">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <span class="text-[10px] font-semibold text-green-600 uppercase tracking-wider">Resolved</span>
                    </div>
                    <p class="text-2xl font-extrabold text-green-700 leading-none">{{ $stats['resolved'] }}</p>
                </div>
            </div>

            {{-- ───── Pending-sync card (TSP-only) ─────
                 Surfaces service reports this TSP has filed that are
                 still waiting to drain to Monday.com. Hidden when
                 zero. --}}
            @if($stats['pending_sync'] > 0)
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-amber-900">
                            {{ $stats['pending_sync'] }} service report{{ $stats['pending_sync'] === 1 ? '' : 's' }} pending sync
                        </p>
                        <p class="text-[11px] text-amber-700">Waiting to mirror to Monday.com — these retry automatically when online.</p>
                    </div>
                </div>
            @endif

            {{-- ───── Ticket list ───── --}}
            <div class="flex items-center justify-between pt-2">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">My Tickets</h3>
                <span class="text-[11px] text-gray-500">From Monday.com · cached 30s</span>
            </div>

            @if(empty($tickets))
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                    <div class="w-16 h-16 mx-auto rounded-full bg-gray-100 text-gray-400 flex items-center justify-center mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No tickets assigned yet</h3>
                    <p class="text-sm text-gray-500 max-w-sm mx-auto">Once tickets are assigned to you in Monday.com, they'll show up here within 30 seconds.</p>
                </div>
            @else
                <div class="space-y-2">
                    @foreach($tickets as $t)
                        @php
                            $statusLower = strtolower((string) $t['status_text']);
                            $statusConfig = match(true) {
                                str_contains($statusLower, 'new') => ['class' => 'bg-blue-100 text-blue-800', 'dot' => 'bg-blue-500'],
                                str_contains($statusLower, 'progress') => ['class' => 'bg-yellow-100 text-yellow-800', 'dot' => 'bg-yellow-500'],
                                str_contains($statusLower, 'awaiting') => ['class' => 'bg-purple-100 text-purple-800', 'dot' => 'bg-purple-500'],
                                str_contains($statusLower, 'resolved') || str_contains($statusLower, 'closed') || str_contains($statusLower, 'done') || str_contains($statusLower, 'complete')
                                    => ['class' => 'bg-green-100 text-green-800', 'dot' => 'bg-green-500'],
                                default => ['class' => 'bg-gray-100 text-gray-800', 'dot' => 'bg-gray-500'],
                            };

                            // Optional brand/model line for the TSP — they
                            // need this at-a-glance since they may have
                            // many open tickets across accounts.
                            $brand = $t['column_values']['text_mm5apcrc']['text'] ?? null;
                            $model = $t['column_values']['text_mm5am2kf']['text'] ?? null;
                        @endphp

                        <a href="{{ route('tsp.tickets.show', $t['id']) }}" class="block bg-white rounded-lg shadow-sm border border-gray-200 hover:border-indigo-300 hover:shadow transition">
                            <div class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-0.5">
                                            <span class="text-[11px] font-mono text-gray-500">#{{ $t['id'] }}</span>
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[10px] font-semibold {{ $statusConfig['class'] }}">
                                                <span class="w-1 h-1 rounded-full {{ $statusConfig['dot'] }}"></span>
                                                {{ $t['status_text'] ?? '—' }}
                                            </span>
                                        </div>
                                        <h3 class="text-sm font-semibold text-gray-900 truncate">
                                            {{ $t['name'] }}
                                        </h3>
                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-0.5 text-[11px] text-gray-500 mt-0.5">
                                            @if($brand || $model)
                                                <span class="inline-flex items-center gap-1">
                                                    🧪 {{ trim(($brand ?? '') . ' ' . (($brand && $model) ? '· ' : '') . ($model ?? '')) }}
                                                </span>
                                            @endif
                                            @if(!empty($t['updates_count']))
                                                <span class="inline-flex items-center gap-1">
                                                    💬 {{ $t['updates_count'] }} update{{ $t['updates_count'] === 1 ? '' : 's' }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
