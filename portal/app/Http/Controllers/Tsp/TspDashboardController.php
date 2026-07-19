<?php

namespace App\Http\Controllers\Tsp;

use App\Http\Controllers\Controller;
use App\Services\MondayClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;

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

        // Unclaimed tickets in the TSP's region — the "pool" of
        // tickets awaiting a field engineer to claim them.
        $unclaimedTickets = [];
        if (! empty($user->region)) {
            try {
                $unclaimedTickets = $monday->unclaimedTicketsForRegion($user->region);
            } catch (\Throwable $e) {
                $unclaimedTickets = [];
            }
        }

        return view('tsp.dashboard', [
            'user'            => $user,
            'tickets'         => $tickets,
            'stats'           => $stats,
            'unclaimedTickets'=> $unclaimedTickets,
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

    /**
     * Claim an unclaimed ticket: write the TSP's person ID into the
     * People column on Monday and flip the response status.
     *
     * This is a POST-only action (no GET). On success the TSP is
     * redirected to the ticket detail page; on failure back to the
     * dashboard with an error flash.
     */
    public function claim(string $id, MondayClient $monday): RedirectResponse
    {
        $user = auth()->user();

        if (empty($user->monday_id)) {
            return back()->withErrors([
                'claim' => 'Your account is not linked to Monday.com. An admin needs to set your monday_id before you can claim tickets.',
            ]);
        }

        try {
            $monday->claimTicket((int) $id, (string) $user->monday_id);
        } catch (\Throwable $e) {
            Log::warning('TspDashboardController::claim failed', [
                'ticket_id' => $id,
                'user_id'   => $user->id,
                'error'     => $e->getMessage(),
            ]);
            return back()->withErrors([
                'claim' => 'Could not claim ticket — Monday.com returned an error. Please try again.',
            ]);
        }

        return redirect()
            ->route('tsp.tickets.show', $id)
            ->with('status', "Ticket #{$id} claimed — it's now in your queue.");
    }
}
