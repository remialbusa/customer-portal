<?php

namespace App\Http\Controllers\Tsp;

use App\Http\Controllers\Controller;
use App\Services\MondayClient;
use Illuminate\Contracts\View\View;

class TspDashboardController extends Controller
{
    public function index(MondayClient $monday): View
    {
        $user = auth()->user();

        // Counters — mirrors the customer dashboard: total / open /
        // in-progress / resolved. `total` is the same as the legacy
        // `assignedCount` (everything assigned to me), kept under the
        // `total` key so the view stays symmetrical with the customer
        // side. `pending_sync` is a TSP-only stat: how many service
        // reports I have filed that are still waiting to drain to
        // Monday.com. The view shows a 5th card when it's > 0.
        $stats = [
            'total'        => 0,
            'open'         => 0,
            'in_progress'  => 0,
            'resolved'     => 0,
            'pending_sync' => 0,
        ];
        $tickets = [];

        if (! empty($user->monday_id)) {
            $tickets = $monday->ticketsForTsp((string) $user->monday_id);

            foreach ($tickets as $t) {
                $stats['total']++;
                $status = strtolower((string) $t['status_text']);
                if ($status === '') {
                    continue;
                }
                if (in_array($status, ['resolved', 'closed', 'done', 'complete'], true)) {
                    $stats['resolved']++;
                } else {
                    $stats['open']++;
                }
                if (str_contains($status, 'progress')) {
                    $stats['in_progress']++;
                }
            }
        }

        // Pending-sync counter: count distinct service reports this
        // TSP has filed that are not yet mirrored to Monday. We
        // query the local DB so this works even when Monday is
        // unreachable. Tsp\Tickets\PendingSyncBadge does the same
        // query live on the page; we do a snapshot here for the
        // dashboard's stats panel.
        try {
            $stats['pending_sync'] = \App\Models\ServiceReport::query()
                ->where('user_id', $user->id)
                ->where('sync_state', '!=', \App\Enums\SyncState::Synced->value)
                ->count();
        } catch (\Throwable $e) {
            // Table might not exist on a fresh install — leave at 0.
            $stats['pending_sync'] = 0;
        }

        return view('tsp.dashboard', [
            'user'    => $user,
            'tickets' => $tickets,
            'stats'   => $stats,
        ]);
    }

    /**
     * Placeholder ticket-detail view. Full chat + internal notes
     * will be built on top of this in Phase 4.
     */
    public function show(string $id, MondayClient $monday): View
    {
        $item = $monday->getItem((int) $id);

        abort_if(! $item, 404, "Ticket {$id} not found in Monday.com.");

        return view('tsp.ticket-show', [
            'user'  => auth()->user(),
            'ticket'=> $item,
        ]);
    }
}
