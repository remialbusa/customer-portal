<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                My Tickets
            </h2>
            <a href="{{ route('tickets.create') }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                + New Ticket
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded px-4 py-3 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-medium text-gray-900">Welcome, {{ $user->name }}</h3>
                        @if($user->account_name)
                            <p class="text-sm text-gray-500">
                                {{ $user->account_name }}
                                @if($user->branch) &middot; {{ $user->branch }} @endif
                            </p>
                        @endif
                    </div>
                    <div class="text-right text-xs text-gray-400">
                        <div>{{ count($tickets) }} ticket{{ count($tickets) === 1 ? '' : 's' }} on file</div>
                    </div>
                </div>

                @if(empty($tickets))
                    <div class="px-6 py-12 text-center">
                        <p class="text-gray-500 mb-4">You have no tickets yet.</p>
                        <a href="{{ route('tickets.create') }}"
                           class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                            Submit your first ticket
                        </a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Group</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($tickets as $t)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <a href="{{ route('tickets.show', $t['id']) }}" class="text-indigo-600 hover:text-indigo-900 hover:underline">
                                                #{{ $t['id'] }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <a href="{{ route('tickets.show', $t['id']) }}" class="text-gray-900 hover:text-indigo-900 hover:underline">
                                                {{ $t['name'] }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @php
                                                $statusLower = strtolower((string) $t['status_text']);
                                                $statusClass = match(true) {
                                                    str_contains($statusLower, 'new')           => 'bg-blue-100 text-blue-800',
                                                    str_contains($statusLower, 'progress')      => 'bg-yellow-100 text-yellow-800',
                                                    str_contains($statusLower, 'awaiting')      => 'bg-purple-100 text-purple-800',
                                                    str_contains($statusLower, 'resolved') || str_contains($statusLower, 'closed') || str_contains($statusLower, 'done') || str_contains($statusLower, 'complete')
                                                                                                  => 'bg-green-100 text-green-800',
                                                    default                                       => 'bg-gray-100 text-gray-800',
                                                };
                                            @endphp
                                            <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium {{ $statusClass }}">
                                                {{ $t['status_text'] ?? '—' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @php
                                                $prioLower = strtolower((string) $t['priority_text']);
                                                $prioClass = match($prioLower) {
                                                    'critical' => 'bg-red-100 text-red-800',
                                                    'high'     => 'bg-orange-100 text-orange-800',
                                                    'medium'   => 'bg-yellow-100 text-yellow-800',
                                                    'low'      => 'bg-gray-100 text-gray-700',
                                                    default    => 'bg-gray-100 text-gray-700',
                                                };
                                            @endphp
                                            <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium {{ $prioClass }}">
                                                {{ $t['priority_text'] ?? '—' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                            {{ $t['request_type_text'] ?? '—' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $t['group'] ?? '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
