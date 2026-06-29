<?php

declare(strict_types=1);

namespace App\Livewire\Tsp\Tickets;

use App\Enums\SyncState;
use App\Models\ServiceReport;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Tiny per-ticket "queued for sync" indicator. Mounts in the ticket
 * list, shows a yellow dot if at least one TSR on this ticket is
 * pending/error, green if all synced, hidden if none yet.
 *
 * Polled every 30s so the badge updates without a full page reload
 * when the offline drainer finishes a batch.
 *
 * Tickets are not a local Eloquent model (they live on Monday.com),
 * so this component takes the Monday item id as a string and queries
 * the local service_reports table directly.
 */
class PendingSyncBadge extends Component
{
    public string $ticketNumber = '';

    public int $pending = 0;
    public int $errored = 0;
    public int $synced  = 0;

    public function mount(string $ticketNumber = ''): void
    {
        $this->ticketNumber = $ticketNumber;
        $this->refresh();
    }

    public function refresh(): void
    {
        if ($this->ticketNumber === '') {
            return;
        }

        $counts = ServiceReport::query()
            ->where('monday_ticket_id', $this->ticketNumber)
            ->selectRaw('sync_state, COUNT(*) as c')
            ->groupBy('sync_state')
            ->pluck('c', 'sync_state')
            ->toArray();

        $this->pending = (int) ($counts[SyncState::Pending->value] ?? 0);
        $this->errored = (int) ($counts[SyncState::Error->value]   ?? 0);
        $this->synced  = (int) ($counts[SyncState::Synced->value]  ?? 0);
    }

    public function render(): View
    {
        return view('livewire.tsp.tickets.pending-sync-badge');
    }
}
