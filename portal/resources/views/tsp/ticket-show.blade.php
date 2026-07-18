<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="min-w-0">
                <p class="text-xs font-semibold tracking-widest uppercase text-base-content/50 mb-1">
                    Service request
                </p>
                <h2 class="font-semibold text-2xl text-base-content leading-tight truncate">
                    Ticket #{{ $ticket['id'] }} &mdash; {{ $ticket['name'] }}
                </h2>
            </div>
            <a href="{{ route('tsp.dashboard') }}" class="btn btn-ghost btn-sm gap-1 self-start sm:self-auto">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back to dashboard
            </a>
        </div>
    </x-slot>

    @php
        $status   = $ticket['column_values']['status95']['text']                  ?? null;
        $rtype    = $ticket['column_values']['request_type']['text']              ?? null;
        $account  = $ticket['column_values']['lookup_mm4f1f6y']['display_value'] ?? null;
        $branch   = $ticket['column_values']['lookup_mm4fj9gp']['display_value'] ?? null;
        $email    = $ticket['column_values']['email']['text']                     ?? null;
        $created  = $ticket['column_values']['date']['text']                      ?? null;
        $desc     = $ticket['column_values']['long_text7']['text']                ?? null;

        // Brand/model — text columns on the Tickets board. Free-text
        // values; render a graceful fallback for legacy tickets where
        // neither column exists.
        $brand    = $ticket['column_values']['text_mm5apcrc']['text'] ?? null;
        $model    = $ticket['column_values']['text_mm5am2kf']['text'] ?? null;

        // Status text → DaisyUI badge tone.
        $statusLower = strtolower((string) $status);
        $statusBadge = match (true) {
            str_contains($statusLower, 'new') || str_contains($statusLower, 'open')
                => 'badge-info',
            str_contains($statusLower, 'progress')
                => 'badge-warning',
            str_contains($statusLower, 'awaiting')
                => 'badge-accent',
            str_contains($statusLower, 'resolved')
                || str_contains($statusLower, 'closed')
                || str_contains($statusLower, 'done')
                || str_contains($statusLower, 'complete')
                => 'badge-success',
            default
                => 'badge-ghost',
        };
    @endphp

    <div class="py-2">
        <div class="max-w-7xl mx-auto sm:px-4 lg:px-6 space-y-6">

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- ───────────────── Customer Information (consolidated) ─────────────────
                     One card containing:
                       • Account hero (icon badge + account name + branch chip)
                       • Status / Type horizontal row (DaisyUI badge)
                       • Affected Machine callout (amber-tinted, brand · model)
                       • Customer email + Submitted 2-column grid
                       • Description (full width)
                     Plus a "Create Service Report" button at the bottom of
                     the card that opens the TSR form in a Bootstrap modal. --}}
                <div class="lg:col-span-1">
                    <x-ui.card padding="p-0">
                        <x-slot:icon>
                            <span aria-hidden="true" class="w-7 h-7 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7l9-4 9 4M3 7l9 4 9-4M3 7v10l9 4 9-4V7M12 11v10"/></svg>
                            </span>
                        </x-slot:icon>

                        <div class="px-5 pb-4 flex items-start gap-3 border-b border-base-300/70">
                            <div class="flex-1 min-w-0">
                                <div class="text-[11px] font-semibold text-base-content/60 uppercase tracking-wider">Account</div>
                                <div class="mt-0.5 text-base font-semibold text-base-content truncate">
                                    {{ $account ?? '—' }}
                                </div>
                                @if ($branch)
                                    <span class="inline-flex items-center mt-1.5 px-2 py-0.5 rounded text-[11px] font-medium bg-base-200 text-base-content/80">
                                        {{ $branch }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Status / Type row (Priority removed — mirrors
                             the customer ticket-show card, which dropped
                             Priority when the field was removed from the
                             create form). --}}
                        <div class="px-5 py-4 grid grid-cols-2 gap-3 border-b border-base-300/70">
                            <div>
                                <div class="text-[11px] font-semibold text-base-content/60 uppercase tracking-wider">Status</div>
                                @if ($status)
                                    <span class="badge {{ $statusBadge }} badge-sm mt-1 gap-1 font-medium">
                                        {{ $status }}
                                    </span>
                                @else
                                    <div class="mt-1 text-sm font-medium text-base-content/60">—</div>
                                @endif
                            </div>
                            <div>
                                <div class="text-[11px] font-semibold text-base-content/60 uppercase tracking-wider">Type</div>
                                <div class="mt-1 text-sm font-medium text-base-content">{{ $rtype ?? '—' }}</div>
                            </div>
                        </div>

                        {{-- Affected Machine callout (amber-tinted). Hidden
                             entirely when both brand and model are missing —
                             legacy tickets shouldn't show an empty card. --}}
                        @if ($brand || $model)
                            <div class="px-5 py-4 border-b border-base-300/70">
                                <div class="rounded-lg bg-warning/10 border border-warning/30 px-3 py-2.5 flex items-center gap-3">
                                    <div class="flex-shrink-0 w-9 h-9 rounded-md bg-warning/20 text-warning flex items-center justify-center" aria-hidden="true">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-[11px] font-semibold text-warning uppercase tracking-wider">Affected machine</div>
                                        <div class="mt-0.5 text-sm font-semibold text-base-content truncate">
                                            @if ($brand && $model)
                                                {{ $brand }} <span class="text-base-content/30" aria-hidden="true">·</span> {{ $model }}
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
                        <div class="px-5 py-4 grid grid-cols-1 sm:grid-cols-2 gap-4 border-b border-base-300/70">
                            <div class="min-w-0">
                                <div class="text-[11px] font-semibold text-base-content/60 uppercase tracking-wider">Customer email</div>
                                <div class="mt-0.5 text-sm text-base-content truncate" title="{{ $email }}">{{ $email ?? '—' }}</div>
                            </div>
                            <div class="min-w-0">
                                <div class="text-[11px] font-semibold text-base-content/60 uppercase tracking-wider">Submitted</div>
                                <div class="mt-0.5 text-sm text-base-content">{{ $created ?? '—' }}</div>
                            </div>
                        </div>

                        {{-- Description --}}
                        @if ($desc)
                            <div class="px-5 py-4 border-b border-base-300/70">
                                <div class="text-[11px] font-semibold text-base-content/60 uppercase tracking-wider">Description</div>
                                <p class="mt-1 text-sm text-base-content whitespace-pre-wrap">{{ $desc }}</p>
                            </div>
                        @endif

                        {{-- Create Service Report — opens an in-place modal
                             that hosts the TSR Livewire form. The modal
                             pattern is preferred over a full-page route
                             because the form is long enough to be
                             disorienting on its own URL, and the user
                             needs the ticket context (description,
                             affected machine) visible right above
                             while filling it in. The `<x-modal>`
                             component is the Breeze Tailwind/Alpine
                             modal — no Bootstrap JS or CDN is involved,
                             so it can't silently fail to open. --}}
                        <div class="px-5 py-4 bg-base-200/60 border-t border-base-300/70 flex items-center justify-between gap-3">
                            <div class="text-xs text-base-content/60">
                                On-site work complete? File the post-service report.
                            </div>
                            <button
                                type="button"
                                class="btn btn-primary btn-sm gap-1.5"
                                x-data
                                x-on:click="$dispatch('open-modal', 'tsr-create-{{ $ticket['id'] }}')"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Create service report
                            </button>
                        </div>
                    </x-ui.card>
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
                    <x-ui.card
                        title="Service report"
                        subtitle="The latest TSR filed against this ticket."
                        padding="p-0"
                    >
                        <x-slot:icon>
                            <span aria-hidden="true" class="w-7 h-7 rounded-lg bg-secondary/10 text-secondary flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </span>
                        </x-slot:icon>
                        <x-slot:actions>
                            <a href="{{ route('tsp.service-reports.show', ['id' => $existingReport['id']]) }}"
                               class="btn btn-ghost btn-sm gap-1">
                                View full report
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        </x-slot:actions>

                        <div class="px-5 py-4 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm border-t border-base-300/70">
                            <div>
                                <div class="text-[11px] font-semibold text-base-content/60 uppercase tracking-wider">Status</div>
                                <div class="mt-0.5 text-base-content">{{ $existingReport['service_status_label'] ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="text-[11px] font-semibold text-base-content/60 uppercase tracking-wider">Filed by</div>
                                <div class="mt-0.5 text-base-content">{{ $existingReport['author_name'] ?? '—' }} · <span class="text-base-content/60">{{ $existingReport['author_role'] ?? '' }}</span></div>
                            </div>
                            <div>
                                <div class="text-[11px] font-semibold text-base-content/60 uppercase tracking-wider">Service window</div>
                                <div class="mt-0.5 text-base-content">
                                    {{ $existingReport['service_start_at'] ?? '—' }}
                                    →
                                    {{ $existingReport['service_end_at'] ?? '—' }}
                                </div>
                            </div>
                            <div>
                                <div class="text-[11px] font-semibold text-base-content/60 uppercase tracking-wider">Total minutes</div>
                                <div class="mt-0.5 text-base-content">{{ $existingReport['total_minutes'] ?? '—' }}</div>
                            </div>
                            @if (! empty($existingReport['job_done']))
                                <div class="sm:col-span-2">
                                    <div class="text-[11px] font-semibold text-base-content/60 uppercase tracking-wider">Job done</div>
                                    <p class="mt-0.5 text-base-content whitespace-pre-wrap">{{ $existingReport['job_done'] }}</p>
                                </div>
                            @endif
                        </div>
                    </x-ui.card>
                @endif
            @endisset
        </div>
    </div>

    {{-- ───────────────────── TSR create modal ─────────────────────
         In-place TSR (Technical Service Report) form. Uses the
         Breeze Tailwind/Alpine `<x-modal>` (no Bootstrap CDN needed)
         so opening is reliable — Alpine is always present on the
         app layout. The modal body is a Livewire component
         (<livewire:tsp.tickets.create-service-report>) which owns
         form state, draft autosave, sync state, and submission.

         Sized 4xl on desktop / fullscreen on mobile so the form has
         room to breathe. --}}
    <x-modal name="tsr-create-{{ $ticket['id'] }}" max-width="2xl" focusable>
        <div class="px-6 py-5 max-h-[85vh] overflow-y-auto" x-data x-on:keydown.escape.window="$dispatch('close-modal', 'tsr-create-{{ $ticket['id'] }}')">
            <livewire:tsp.tickets.create-service-report :ticket-number="(string) $ticket['id']" />
        </div>
    </x-modal>

</x-app-layout>
