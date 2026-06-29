<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Ticket #{{ $ticket['id'] }} &mdash; {{ $ticket['name'] }}
            </h2>
            <a href="{{ route('dashboard') }}" class="text-sm text-indigo-600 hover:underline">
                &larr; Back to tickets
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
                            $created  = $ticket['column_values']['date']['text']             ?? null;
                            $desc     = $ticket['column_values']['long_text7']['text']       ?? null;
                        @endphp

                        <div>
                            <dt class="text-xs text-gray-500 uppercase">Status</dt>
                            <dd class="mt-1 text-gray-900">{{ $status ?? '—' }}</dd>
                        </div>

                        {{-- Real-time status banner (Phase 6). Subscribes
                             to the ticket.{id}.customer Pusher channel
                             and reacts to TSP service-report events. --}}
                        <div
                            x-data="ticketStatusBanner({
                                ticketId: @js($ticket['id']),
                                currentStatus: @js($status),
                            })"
                            x-init="init()"
                            class="space-y-3 pt-3 border-t border-gray-100"
                        >
                            <div
                                x-show="flash"
                                x-cloak
                                class="rounded-md px-3 py-2 text-sm flex items-start justify-between gap-2"
                                :class="{
                                    'bg-emerald-50 text-emerald-800 border border-emerald-200': flash && flash.kind === 'status' && newStatus && newStatus.toLowerCase() === 'resolved',
                                    'bg-indigo-50  text-indigo-800  border border-indigo-200' : flash && flash.kind === 'report',
                                    'bg-amber-50   text-amber-800   border border-amber-200'  : flash && flash.kind === 'status',
                                }"
                            >
                                <span x-text="flash ? flash.message : ''"></span>
                                <button
                                    type="button"
                                    @click="dismissFlash()"
                                    class="text-xs underline"
                                >dismiss</button>
                            </div>

                            <div
                                x-show="resolution"
                                x-cloak
                                class="rounded-md border border-emerald-200 bg-emerald-50 p-3 text-sm"
                            >
                                <div class="text-xs font-semibold text-emerald-800 uppercase">
                                    Service complete
                                </div>
                                <p class="mt-1 text-gray-800 whitespace-pre-wrap" x-text="resolution?.customer_summary"></p>
                                <p class="mt-2 text-[11px] text-emerald-700">
                                    Resolved by
                                    <span x-text="resolution?.author_name"></span>
                                </p>
                            </div>
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

        </div>
    </div>
</x-app-layout>
