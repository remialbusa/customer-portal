<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Account deletion requests
            </h2>
            <a href="{{ route('admin.invites') }}"
               class="text-sm text-gray-500 hover:text-gray-700 underline-offset-2 hover:underline">
                ← Customer invites
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-sm text-red-800">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- ───────  Pending  ─────── --}}
            <div class="bg-white shadow-sm rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Pending</h3>
                    <span class="text-xs text-gray-500">{{ $pending->count() }} awaiting review</span>
                </div>

                @if ($pending->isEmpty())
                    <p class="px-6 py-8 text-sm text-gray-500 text-center">
                        No pending deletion requests. 🎉
                    </p>
                @else
                    <ul class="divide-y divide-gray-200">
                        @foreach ($pending as $req)
                            <li class="px-6 py-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 break-words">
                                            {{ $req->name ?? '(no name)' }}
                                            <span class="text-xs text-gray-500 font-normal">— {{ $req->email }}</span>
                                        </p>
                                        <p class="mt-1 text-xs text-gray-500">
                                            Role:
                                            <span class="font-medium text-gray-700">{{ $req->role ?? '—' }}</span>
                                            <span class="mx-2 text-gray-300">·</span>
                                            Filed {{ $req->created_at->diffForHumans() }}
                                            ({{ $req->created_at->format('M j, Y g:i A') }})
                                        </p>
                                        @if ($req->reason)
                                            <p class="mt-2 text-sm text-gray-700 bg-gray-50 border border-gray-200 rounded px-3 py-2 italic whitespace-pre-wrap">
                                                "{{ $req->reason }}"
                                            </p>
                                        @endif
                                    </div>

                                    <div class="flex flex-col gap-2 shrink-0">
                                        {{-- Approve: actually deletes the user. --}}
                                        <form
                                            method="POST"
                                            action="{{ route('admin.deletion-requests.approve', $req) }}"
                                            onsubmit="return confirm('Approve and PERMANENTLY delete the account for {{ $req->email }}? This cannot be undone.');"
                                        >
                                            @csrf
                                            <button
                                                type="submit"
                                                class="inline-flex items-center px-3 py-1.5 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition"
                                            >
                                                Approve & Delete
                                            </button>
                                        </form>

                                        {{-- Reject: keeps the user, marks request as rejected. --}}
                                        <form
                                            method="POST"
                                            action="{{ route('admin.deletion-requests.reject', $req) }}"
                                            onsubmit="return confirm('Reject this deletion request?');"
                                        >
                                            @csrf
                                            <input type="hidden" name="decision_note" value="Rejected by {{ $user->name }}">
                                            <button
                                                type="submit"
                                                class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition"
                                            >
                                                Reject
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- ───────  Recent activity  ─────── --}}
            <div class="bg-white shadow-sm rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Recent decisions</h3>
                </div>
                @if ($recent->isEmpty())
                    <p class="px-6 py-8 text-sm text-gray-500 text-center">
                        No decisions yet.
                    </p>
                @else
                    <ul class="divide-y divide-gray-200">
                        @foreach ($recent as $req)
                            <li class="px-6 py-3 text-sm">
                                <div class="flex items-center justify-between gap-4">
                                    <div class="flex-1 min-w-0">
                                        <span class="font-medium text-gray-900">{{ $req->email }}</span>
                                        <span class="text-xs text-gray-500 ml-2">
                                            {{ ucfirst($req->status) }}
                                            by {{ $req->processor?->name ?? '—' }}
                                            on {{ $req->processed_at?->format('M j, Y g:i A') }}
                                        </span>
                                        @if ($req->decision_note)
                                            <p class="text-xs text-gray-500 mt-0.5 italic">{{ $req->decision_note }}</p>
                                        @endif
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
