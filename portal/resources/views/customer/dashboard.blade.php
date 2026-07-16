<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Service Dashboard
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    Welcome back, {{ $user->name }}
                    @if($user->account_name)
                        — {{ $user->account_name }}
                    @endif
                </p>
            </div>
            <a href="{{ route('tickets.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 border border-transparent rounded-lg font-medium text-sm text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New Service Request
            </a>
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

            {{-- ───── Stats cards (2x2 grid) ───── --}}
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

            {{-- ───── Ticket list ───── --}}
            @if(empty($tickets))
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                    <div class="w-16 h-16 mx-auto rounded-full bg-gray-100 text-gray-400 flex items-center justify-center mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No service requests yet</h3>
                    <p class="text-sm text-gray-500 mb-6 max-w-sm mx-auto">Submit your first service request and our team will get back to you.</p>
                    <a href="{{ route('tickets.create') }}"
                       class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 border border-transparent rounded-lg font-medium text-sm text-white shadow-sm hover:bg-indigo-700 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Submit Your First Request
                    </a>
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
                        @endphp

                        <a href="{{ route('tickets.show', $t['id']) }}" class="block bg-white rounded-lg shadow-sm border border-gray-200 hover:border-indigo-300 hover:shadow transition">
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
                                            @if(!empty($t['request_type_text']))
                                                <span class="inline-flex items-center gap-1">
                                                    {{ $t['request_type_text'] }}
                                                </span>
                                            @endif
                                            @if(!empty($t['updates_count']))
                                                <span class="inline-flex items-center gap-1">
                                                    {{ $t['updates_count'] }} update{{ $t['updates_count'] === 1 ? '' : 's' }}
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
