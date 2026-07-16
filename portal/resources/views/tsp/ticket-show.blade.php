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
                {{-- ───────────────── Customer Information (consolidated) ─────────────────
                     One card containing:
                       • Account hero (hospital icon + account name + branch chip)
                       • Status / Priority / Type horizontal row
                       • Affected Machine callout (amber-tinted, brand · model)
                       • Customer email + Submitted 2-column grid
                       • Description (full width)
                     Plus a "Create Service Report" button at the bottom of
                     the card that opens the TSR form in a Bootstrap modal. --}}
                @php
                    $status   = $ticket['column_values']['status95']['text']                  ?? null;
                    $rtype    = $ticket['column_values']['request_type']['text']              ?? null;
                    $account  = $ticket['column_values']['lookup_mm4f1f6y']['display_value'] ?? null;
                    $branch   = $ticket['column_values']['lookup_mm4fj9gp']['display_value'] ?? null;
                    $email    = $ticket['column_values']['email']['text']                     ?? null;
                    $created  = $ticket['column_values']['date']['text']                      ?? null;
                    $desc     = $ticket['column_values']['long_text7']['text']                ?? null;

                    // Brand/model — text columns on the Tickets board. Free-text
                    // values; render a graceful fallback for legacy tickets
                    // where neither column exists.
                    $brand    = $ticket['column_values']['text_mm5apcrc']['text'] ?? null;
                    $model    = $ticket['column_values']['text_mm5am2kf']['text'] ?? null;
                @endphp

                <div class="lg:col-span-1 bg-white shadow sm:rounded-lg overflow-hidden">
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

                    {{-- Status / Type row (Priority removed — mirrors
                         the customer ticket-show card, which dropped
                         Priority when the field was removed from the
                         create form). --}}
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

                    {{-- Customer email + Submitted 2-column grid --}}
                    <div class="px-6 py-4 grid grid-cols-1 sm:grid-cols-2 gap-4 border-b border-gray-100">
                        <div class="min-w-0">
                            <div class="text-[11px] font-medium text-gray-500 uppercase tracking-wide">Customer email</div>
                            <div class="mt-0.5 text-sm text-gray-900 truncate" title="{{ $email }}">{{ $email ?? '—' }}</div>
                        </div>
                        <div class="min-w-0">
                            <div class="text-[11px] font-medium text-gray-500 uppercase tracking-wide">Submitted</div>
                            <div class="mt-0.5 text-sm text-gray-900">{{ $created ?? '—' }}</div>
                        </div>
                    </div>

                    {{-- Description --}}
                    @if ($desc)
                        <div class="px-6 py-4 border-b border-gray-100">
                            <div class="text-[11px] font-medium text-gray-500 uppercase tracking-wide">Description</div>
                            <p class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{{ $desc }}</p>
                        </div>
                    @endif

                    {{-- Create Service Report button (opens the TSR modal
                         below). Hidden when the ticket is in a "closed"
                         state — the user can still view the report via
                         the "View" link if one was filed. --}}
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between gap-3">
                        <div class="text-xs text-gray-500">
                            On-site work complete? File the post-service report.
                        </div>
                        <button
                            type="button"
                            class="inline-flex items-center px-3 py-1.5 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest shadow-sm hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition"
                            data-bs-toggle="modal"
                            data-bs-target="#tsrModal"
                        >
                            📝 Create Service Report
                        </button>
                    </div>
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

            {{-- Existing service report (read-only). Renders a small
                 card showing the latest TSR filed against this ticket,
                 with a link to the full detail page. Only renders when
                 at least one report exists for this ticket. --}}
            @isset($existingReport)
                @if ($existingReport)
                    <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="text-base font-semibold text-gray-900">Service report</h3>
                            <a href="{{ route('tsp.service-reports.show', ['id' => $existingReport['id']]) }}"
                               class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                                View full report &rarr;
                            </a>
                        </div>
                        <div class="px-6 py-4 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                            <div>
                                <div class="text-[11px] font-medium text-gray-500 uppercase tracking-wide">Status</div>
                                <div class="mt-0.5 text-gray-900">{{ $existingReport['service_status_label'] ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="text-[11px] font-medium text-gray-500 uppercase tracking-wide">Filed by</div>
                                <div class="mt-0.5 text-gray-900">{{ $existingReport['author_name'] ?? '—' }} · <span class="text-gray-500">{{ $existingReport['author_role'] ?? '' }}</span></div>
                            </div>
                            <div>
                                <div class="text-[11px] font-medium text-gray-500 uppercase tracking-wide">Service window</div>
                                <div class="mt-0.5 text-gray-900">
                                    {{ $existingReport['service_start_at'] ?? '—' }}
                                    →
                                    {{ $existingReport['service_end_at'] ?? '—' }}
                                </div>
                            </div>
                            <div>
                                <div class="text-[11px] font-medium text-gray-500 uppercase tracking-wide">Total minutes</div>
                                <div class="mt-0.5 text-gray-900">{{ $existingReport['total_minutes'] ?? '—' }}</div>
                            </div>
                            @if (! empty($existingReport['job_done']))
                                <div class="sm:col-span-2">
                                    <div class="text-[11px] font-medium text-gray-500 uppercase tracking-wide">Job done</div>
                                    <p class="mt-0.5 text-gray-900 whitespace-pre-wrap">{{ $existingReport['job_done'] }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            @endisset
        </div>
    </div>

    {{-- ───────────────── TSR modal (Phase 6) ─────────────────
         The full CreateServiceReport Livewire form lives inside a
         Bootstrap 5 modal. The modal is opened by the "Create
         Service Report" button above (data-bs-toggle="modal").

         The modal is full-screen on mobile and large on desktop
         so the form's sticky bars and signature canvases are
         usable. Bootstrap JS is loaded lazily below. --}}
    <div
        class="modal fade"
        id="tsrModal"
        tabindex="-1"
        aria-labelledby="tsrModalTitle"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-lg-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tsrModalTitle">
                        Service Report — Ticket #{{ $ticket['id'] }}
                    </h5>
                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                    ></button>
                </div>
                <div class="modal-body p-0">
                    {{-- The TSR form expects `ticket-number` (string) — see
                         CreateServiceReport::mount($ticketNumber). Passing
                         the whole ticket array would fail type coercion. --}}
                    <livewire:tsp.tickets.create-service-report
                        :ticket-number="$ticket['id']" />
                </div>
            </div>
        </div>
    </div>

    {{-- Bootstrap 5.3 JS bundle — needed for the modal above. The
         layout's <head> only ships the CSS; we lazy-load the JS
         on this page to keep the rest of the portal lightweight. --}}
    @once
        @push('scripts')
            <script
                src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
                crossorigin="anonymous"
                defer></script>
        @endpush
    @endonce
</x-app-layout>
