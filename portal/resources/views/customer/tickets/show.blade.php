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
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="grid grid-cols-1 gap-6">
                {{-- ───────────────── Customer Information (consolidated) ─────────────────
                     One card containing:
                       • Account hero (hospital icon + account name + branch chip)
                       • Status / Type horizontal row
                       • Affected Machine callout (amber-tinted, brand · model)
                       • Submitted (single column on customer view — no email row)
                       • Real-time status banner (still embedded here so it
                         stays anchored under the customer's machine info)
                       • Description (full width)
                     The Customer ticket view does NOT show the customer's
                     own email — that lives in the page nav header. --}}
                @php
                    $status   = $ticket['column_values']['status95']['text']                  ?? null;
                    $rtype    = $ticket['column_values']['request_type']['text']              ?? null;
                    $account  = $ticket['column_values']['lookup_mm4f1f6y']['display_value'] ?? null;
                    $branch   = $ticket['column_values']['lookup_mm4fj9gp']['display_value'] ?? null;
                    $created  = $ticket['column_values']['date']['text']                      ?? null;
                    $desc     = $ticket['column_values']['long_text7']['text']                ?? null;

                    // Brand/model — text columns on the Tickets board. Customers
                    // can supply free-text (it's not strictly typed in the catalog)
                    // so we render a graceful fallback for legacy tickets where
                    // neither column exists.
                    $brand    = $ticket['column_values']['text_mm5apcrc']['text'] ?? null;
                    $model    = $ticket['column_values']['text_mm5am2kf']['text'] ?? null;
                @endphp

                <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                    {{-- Hero: account name + branch chip --}}
                    <div class="px-6 pt-5 pb-4 border-b border-gray-100 flex items-start gap-3">
                        <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl" aria-hidden="true">
                            🏥
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Account</div>
                            <div class="mt-0.5 text-base font-semibold text-gray-900 truncate">
                                {{ $account ?? '—' }}
                            </div>
                            @if ($branch)
                                <span class="inline-flex items-center mt-1.5 px-2 py-0.5 rounded text-[11px] font-medium bg-gray-100 text-gray-700">
                                    {{ $branch }}
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Status / Type row --}}
                    <div class="px-6 py-4 grid grid-cols-2 gap-3 border-b border-gray-100">
                        <div>
                            <div class="text-[11px] font-medium text-gray-500 uppercase tracking-wide">Status</div>
                            <div class="mt-1 text-sm font-medium text-gray-900">{{ $status ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-[11px] font-medium text-gray-500 uppercase tracking-wide">Type</div>
                            <div class="mt-1 text-sm font-medium text-gray-900">{{ $rtype ?? '—' }}</div>
                        </div>
                    </div>

                    {{-- Affected Machine callout (amber-tinted). Hidden
                         entirely when both brand and model are missing —
                         legacy tickets shouldn't show an empty card. --}}
                    @if ($brand || $model)
                        <div class="px-6 py-4 border-b border-gray-100">
                            <div class="rounded-lg bg-amber-50 border border-amber-200 px-3 py-2.5 flex items-center gap-3">
                                <div class="flex-shrink-0 w-9 h-9 rounded-md bg-amber-100 text-amber-700 flex items-center justify-center text-lg" aria-hidden="true">
                                    🧪
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-[11px] font-medium text-amber-800 uppercase tracking-wide">Affected machine</div>
                                    <div class="mt-0.5 text-sm font-semibold text-amber-900 truncate">
                                        @if ($brand && $model)
                                            {{ $brand }} <span class="text-amber-500" aria-hidden="true">·</span> {{ $model }}
                                        @elseif ($brand)
                                            {{ $brand }}
                                        @else
                                            {{ $model }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Submitted (single column — customer view doesn't show
                         the customer's own email here; that's already in the
                         page header / nav). --}}
                    <div class="px-6 py-4 border-b border-gray-100">
                        <div class="text-[11px] font-medium text-gray-500 uppercase tracking-wide">Submitted</div>
                        <div class="mt-0.5 text-sm text-gray-900">{{ $created ?? '—' }}</div>
                    </div>

                    {{-- Real-time status banner (Phase 6). Subscribes
                         to the ticket.{id}.customer Pusher channel
                         and reacts to TSP service-report events. --}}
                    <div class="px-6 py-4 border-b border-gray-100">
                        <div
                            x-data="ticketStatusBanner({
                                ticketId: @js($ticket['id']),
                                currentStatus: @js($status),
                            })"
                            x-init="init()"
                            class="space-y-3"
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
                    </div>

                    {{-- Description --}}
                    @if ($desc)
                        <div class="px-6 py-4">
                            <div class="text-[11px] font-medium text-gray-500 uppercase tracking-wide">Description</div>
                            <p class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{{ $desc }}</p>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>

    {{-- Floating chat bubble (replaces the full-width chat panel) --}}
    <livewire:chat-bubble
        :ticket-id="$ticket['id']"
        :current-user-name="$user->name"
        :current-user-role="$user->role"
        :messages="$messages"
    />
</x-app-layout>
