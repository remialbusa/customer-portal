<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="min-w-0">
                <p class="text-xs font-semibold tracking-widest uppercase text-base-content/50 mb-1">
                    Service request
                </p>
                <h2 class="font-semibold text-2xl text-base-content leading-tight truncate">
                    Ticket #{{ $ticket['id'] }} &mdash; {{ $ticket['column_values']['text_mm5c1w5n']['text'] ?: $ticket['name'] }}
                </h2>
            </div>
            <a href="{{ route('dashboard') }}" class="btn btn-ghost btn-sm gap-1 self-start sm:self-auto">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back to tickets
            </a>
        </div>
    </x-slot>

    @php
        $status   = $ticket['column_values']['status95']['text']                  ?? null;
        $rtype    = $ticket['column_values']['request_type']['text']              ?? null;
        $account  = $ticket['column_values']['lookup_mm4f1f6y']['display_value'] ?? null;
        $branch   = $ticket['column_values']['lookup_mm4fj9gp']['display_value'] ?? null;
        $created  = $ticket['column_values']['date']['text']                      ?? null;
        $desc     = $ticket['column_values']['long_text7']['text']                ?? null;
        $subject  = $ticket['column_values']['text_mm5c1w5n']['text']             ?? null;

        // Brand/model — text columns on the Tickets board. Customers
        // can supply free-text (it's not strictly typed in the catalog)
        // so we render a graceful fallback for legacy tickets where
        // neither column exists.
        $brand    = $ticket['column_values']['text_mm5apcrc']['text'] ?? null;
        $model    = $ticket['column_values']['text_mm5am2kf']['text'] ?? null;

        // Map status text → DaisyUI badge tone (matches the customer
        // dashboard / create form so this view feels consistent).
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
        <div class="max-w-3xl mx-auto sm:px-4 lg:px-6 space-y-6">

            {{-- ───────────────── Customer Information (consolidated) ─────────────────
                 One card containing:
                   • Account hero (icon badge + account name + branch chip)
                   • Status / Type horizontal row (DaisyUI badge for status)
                   • Affected Machine callout (amber-tinted, brand · model)
                   • Submitted (single column on customer view — no email row)
                   • Real-time status banner (still embedded here so it
                     stays anchored under the customer's machine info)
                   • Description (full width)
                 The Customer ticket view does NOT show the customer's
                 own email — that lives in the page nav header. --}}
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

                {{-- Status / Type row --}}
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

                {{-- Submitted (single column — customer view doesn't show
                     the customer's own email here; that's already in the
                     page header / nav). --}}
                <div class="px-5 py-4 border-b border-base-300/70">
                    <div class="text-[11px] font-semibold text-base-content/60 uppercase tracking-wider">Submitted</div>
                    <div class="mt-0.5 text-sm text-base-content">{{ $created ?? '—' }}</div>
                </div>

                {{-- Subject --}}
                @if ($subject)
                    <div class="px-5 py-4 border-b border-base-300/70">
                        <div class="text-[11px] font-semibold text-base-content/60 uppercase tracking-wider">Subject</div>
                        <div class="mt-0.5 text-sm font-medium text-base-content">{{ $subject }}</div>
                    </div>
                @endif

                {{-- Description --}}
                @if ($desc)
                    <div class="px-5 py-4">
                        <div class="text-[11px] font-semibold text-base-content/60 uppercase tracking-wider">Description</div>
                        <p class="mt-1 text-sm text-base-content whitespace-pre-wrap">{{ $desc }}</p>
                    </div>
                @endif
            </x-ui.card>
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
