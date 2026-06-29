<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Ticket #{{ $ticket['id'] }} &mdash; {{ $ticket['name'] }}
            </h2>
            <a href="{{ route('tsp.dashboard') }}" class="text-sm text-indigo-600 hover:underline">
                &larr; Back to dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Ticket details --}}
                <div class="lg:col-span-1 bg-white shadow sm:rounded-lg p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Details</h3>
                    <dl class="space-y-3 text-sm">
                        @php
                            $status   = $ticket['column_values']['status95']['text']        ?? null;
                            $priority = $ticket['column_values']['priority']['text']         ?? null;
                            $rtype    = $ticket['column_values']['request_type']['text']    ?? null;
                            $account  = $ticket['column_values']['lookup_mm4f1f6y']['display_value'] ?? null;
                            $branch   = $ticket['column_values']['lookup_mm4fj9gp']['display_value'] ?? null;
                            $email    = $ticket['column_values']['email']['text']           ?? null;
                            $created  = $ticket['column_values']['date']['text']             ?? null;
                            $desc     = $ticket['column_values']['long_text7']['text']       ?? null;
                        @endphp

                        <div>
                            <dt class="text-xs text-gray-500 uppercase">Status</dt>
                            <dd class="mt-1 text-gray-900">{{ $status ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 uppercase">Priority</dt>
                            <dd class="mt-1 text-gray-900">{{ $priority ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 uppercase">Type</dt>
                            <dd class="mt-1 text-gray-900">{{ $rtype ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 uppercase">Account</dt>
                            <dd class="mt-1 text-gray-900">{{ $account ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 uppercase">Branch</dt>
                            <dd class="mt-1 text-gray-900">{{ $branch ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 uppercase">Customer email</dt>
                            <dd class="mt-1 text-gray-900">{{ $email ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 uppercase">Submitted</dt>
                            <dd class="mt-1 text-gray-900">{{ $created ?? '—' }}</dd>
                        </div>
                        @if ($desc)
                            <div class="pt-3 border-t border-gray-100">
                                <dt class="text-xs text-gray-500 uppercase">Description</dt>
                                <dd class="mt-1 text-gray-900 whitespace-pre-wrap">{{ $desc }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>

                {{-- Chat panel --}}
                <div class="lg:col-span-2">
                    <livewire:chat-panel
                        :ticket-id="$ticket['id']"
                        :current-user-name="$user->name"
                        :current-user-role="$user->role"
                        :messages="$messages"
                    />
                </div>
            </div>

            {{-- Time tracker (Phase 5) --}}
            <livewire:time-tracker
                :ticket-id="$ticket['id']"
                :current-user-name="$user->name"
                :current-user-role="$user->role"
                :active="$timeActive"
                :total-seconds="$timeTotal"
            />

            {{-- Internal notes panel (TSP-only) --}}
            <livewire:internal-notes-panel
                :ticket-id="$ticket['id']"
                :current-user-name="$user->name"
                :current-user-role="$user->role"
                :notes="$notes"
            />

            {{-- Service report (Phase 6, offline-first): TSP fills
                 this in after on-site work. Submission writes to the
                 local DB; a separate drainer mirrors to Monday. --}}
            <livewire:tsp.tickets.create-service-report
                :ticket-number="$ticket['id']" />

        </div>
    </div>
</x-app-layout>

