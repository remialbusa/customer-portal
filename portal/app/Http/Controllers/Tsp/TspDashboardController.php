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

        // Counters
        $openCount       = 0;
        $inProgressCount = 0;
        $assignedCount   = 0;
        $tickets         = [];

        if (! empty($user->monday_id)) {
            $tickets = $monday->ticketsForTsp((string) $user->monday_id);

            foreach ($tickets as $t) {
                $assignedCount++;
                $status = strtolower((string) $t['status_text']);
                if ($status !== '' && ! in_array($status, ['resolved', 'closed'], true)) {
                    $openCount++;
                }
                if (str_contains($status, 'progress')) {
                    $inProgressCount++;
                }
            }
        }

        return view('tsp.dashboard', [
            'user'            => $user,
            'tickets'         => $tickets,
            'openCount'       => $openCount,
            'inProgressCount' => $inProgressCount,
            'assignedCount'   => $assignedCount,
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
