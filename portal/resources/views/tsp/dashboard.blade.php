<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            TSP Dashboard
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Greeting --}}
            <div class="bg-white p-6 rounded shadow">
                <p class="text-sm text-gray-500">
                    Logged in as <span class="font-medium">{{ $user->name }}</span>
                    &middot; <span class="uppercase">{{ $user->role }}</span>
                    @if($user->team) &middot; {{ $user->team }} @endif
                    @if($user->region) &middot; {{ $user->region }} @endif
                </p>
                @if(empty($user->monday_id))
                    <p class="mt-2 text-sm text-amber-700 bg-amber-50 p-3 rounded">
                        Your account is not yet linked to a Monday.com person.
                        Tickets won't show up until an admin sets your <code>monday_id</code>.
                    </p>
                @endif
            </div>

            {{-- Stat cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white p-5 rounded shadow">
                    <div class="text-xs uppercase text-gray-500">Assigned to me</div>
                    <div class="text-3xl font-bold mt-1">{{ $assignedCount }}</div>
                </div>
                <div class="bg-white p-5 rounded shadow">
                    <div class="text-xs uppercase text-gray-500">Open</div>
                    <div class="text-3xl font-bold mt-1 text-blue-600">{{ $openCount }}</div>
                </div>
                <div class="bg-white p-5 rounded shadow">
                    <div class="text-xs uppercase text-gray-500">In progress</div>
                    <div class="text-3xl font-bold mt-1 text-amber-600">{{ $inProgressCount }}</div>
                </div>
            </div>

            {{-- Tickets table --}}
            <div class="bg-white rounded shadow">
                <div class="px-6 py-4 border-b flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">My Tickets</h3>
                    <span class="text-xs text-gray-500">From Monday.com &middot; cached 30s</span>
                </div>

                @if(empty($tickets))
                    <div class="p-6 text-gray-500 text-sm">
                        No tickets assigned to you yet. Once tickets are assigned in
                        Monday.com, they'll show up here within 30 seconds.
                    </div>
                @else
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                            <tr>
                                <th class="px-6 py-3 text-left">Ticket</th>
                                <th class="px-6 py-3 text-left">Status</th>
                                <th class="px-6 py-3 text-left">Priority</th>
                                <th class="px-6 py-3 text-left">Request Type</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach($tickets as $t)
                                <tr>
                                    <td class="px-6 py-3 font-medium text-gray-900">
                                        #{{ $t['id'] }} &mdash; {{ $t['name'] }}
                                    </td>
                                    <td class="px-6 py-3">
                                        <span class="px-2 py-1 rounded text-xs
                                            @if(str_contains(strtolower($t['status_text'] ?? ''), 'progress')) bg-amber-100 text-amber-800
                                            @elseif(str_contains(strtolower($t['status_text'] ?? ''), 'new')) bg-blue-100 text-blue-800
                                            @elseif(str_contains(strtolower($t['status_text'] ?? ''), 'hold')) bg-gray-200 text-gray-800
                                            @elseif(in_array(strtolower($t['status_text'] ?? ''), ['resolved','closed'])) bg-green-100 text-green-800
                                            @else bg-gray-100 text-gray-800 @endif">
                                            {{ $t['status_text'] ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3">{{ $t['priority_text'] ?? '—' }}</td>
                                    <td class="px-6 py-3">{{ $t['request_type_text'] ?? '—' }}</td>
                                    <td class="px-6 py-3 text-right">
                                        <a href="{{ route('tsp.tickets.show', $t['id']) }}"
                                           class="text-indigo-600 hover:underline text-xs">
                                            Open
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
