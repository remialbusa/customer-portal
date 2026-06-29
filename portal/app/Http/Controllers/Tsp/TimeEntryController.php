<?php

namespace App\Http\Controllers\Tsp;

use App\Exceptions\ExistingTimerException;
use App\Exceptions\TicketNotFoundException;
use App\Http\Controllers\Controller;
use App\Models\TimeEntry;
use App\Services\TimeTracker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Time tracker actions for the TSP ticket page.
 *
 * Every method returns a JsonResponse with:
 *   { ok: true, active: ?array, total_seconds: int, message: ?string }
 *
 * The active array (or null) is consumed by both the Livewire Volt
 * component and the Alpine factory via the 'time-tracker:state' window
 * event.
 */
class TimeEntryController extends Controller
{
    public function __construct(
        protected TimeTracker $tracker,
    ) {
    }

    /**
     * Start a new timer on $id. If the user already has an open/paused
     * entry on this ticket, returns the existing one (idempotent). If
     * the user has one on a DIFFERENT ticket, returns 409 with the
     * conflicting entry.
     */
    public function start(Request $request, string $id): JsonResponse
    {
        $mondayId = (int) $id;
        $user     = $request->user();
        $note     = trim((string) $request->input('note', ''));

        try {
            $entry = $this->tracker->start($user, $mondayId, $note !== '' ? $note : null);
        } catch (ExistingTimerException $e) {
            return response()->json([
                'ok'        => false,
                'code'      => 'existing_timer',
                'message'   => "You already have a timer running on ticket #{$e->existing->monday_ticket_id}.",
                'active'    => $this->serialize($e->existing),
            ], 409);
        } catch (TicketNotFoundException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 404);
        }

        return response()->json([
            'ok'            => true,
            'active'        => $this->serialize($entry),
            'total_seconds' => $this->tracker->totalSecondsForTicket($mondayId),
            'message'       => 'Timer started.',
        ]);
    }

    public function pause(Request $request, string $id): JsonResponse
    {
        $mondayId = (int) $id;
        $user     = $request->user();

        $entry = $this->tracker->activeEntryFor($user);
        if (! $entry || (int) $entry->monday_ticket_id !== $mondayId) {
            return response()->json(['ok' => false, 'message' => 'No active timer on this ticket.'], 404);
        }

        try {
            $entry = $this->tracker->pause($entry);
        } catch (\Throwable $e) {
            Log::warning('TimeEntryController: pause failed', ['error' => $e->getMessage()]);
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'ok'      => true,
            'active'  => $this->serialize($entry),
            'message' => 'Timer paused.',
        ]);
    }

    public function resume(Request $request, string $id): JsonResponse
    {
        $mondayId = (int) $id;
        $user     = $request->user();

        $entry = $this->tracker->activeEntryFor($user);
        if (! $entry || (int) $entry->monday_ticket_id !== $mondayId) {
            return response()->json(['ok' => false, 'message' => 'No paused timer on this ticket.'], 404);
        }

        try {
            $entry = $this->tracker->resume($entry);
        } catch (\Throwable $e) {
            Log::warning('TimeEntryController: resume failed', ['error' => $e->getMessage()]);
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'ok'      => true,
            'active'  => $this->serialize($entry),
            'message' => 'Timer resumed.',
        ]);
    }

    public function stop(Request $request, string $id): JsonResponse
    {
        $mondayId = (int) $id;
        $user     = $request->user();

        $entry = $this->tracker->activeEntryFor($user);
        if (! $entry || (int) $entry->monday_ticket_id !== $mondayId) {
            return response()->json(['ok' => false, 'message' => 'No active timer on this ticket.'], 404);
        }

        try {
            $entry = $this->tracker->stop($entry);
        } catch (\Throwable $e) {
            Log::warning('TimeEntryController: stop failed', ['error' => $e->getMessage()]);
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'ok'            => true,
            'active'        => null,
            'total_seconds' => $this->tracker->totalSecondsForTicket($mondayId),
            'message'       => "Logged {$entry->elapsedFormatted()} to Monday.",
        ]);
    }

    protected function serialize(?TimeEntry $entry): ?array
    {
        if (! $entry) {
            return null;
        }
        return [
            'id'              => (int) $entry->id,
            'monday_ticket_id'=> (int) $entry->monday_ticket_id,
            'status'          => $entry->status,
            'elapsed_seconds' => (int) $entry->elapsedSeconds(),
            'started_at'      => $entry->started_at?->toIso8601String(),
            'note'            => $entry->note,
        ];
    }
}
