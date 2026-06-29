<?php

namespace App\Http\Controllers\Tsp;

use App\Events\InternalNoteAdded;
use App\Http\Controllers\Concerns\AssertsTicketAccess;
use App\Http\Controllers\Controller;
use App\Models\InternalNote;
use App\Models\ServiceReport;
use App\Models\TimeEntry;
use App\Services\MondayClient;
use App\Services\TimeTracker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * TSP-only internal notes on a ticket.
 *
 * Notes are stored locally in `internal_notes` (one row per note, full
 * audit trail with author + timestamp). The most-recent note's body is
 * mirrored to a dedicated long-text column on the Tickets board in
 * Monday (config('services.monday.tickets_columns.internal_notes')).
 * Customers never see this surface.
 */
class InternalNoteController extends Controller
{
    use AssertsTicketAccess;

    /**
     * Render the TSP ticket detail with chat + internal-notes panels.
     */
    public function show(string $id, TimeTracker $tracker): View
    {
        $user = auth()->user();
        $item = $this->loadMondayTicket($id);
        $this->authorizeTicketAccess($user, $item);

        $messages = $this->loadMessageHistory($id, $user);
        $notes    = $this->loadNoteHistory($id);

        // Phase 5: time tracker state for the page.
        $active        = $tracker->activeEntryFor($user);
        $totalSeconds  = $tracker->totalSecondsForTicket((int) $id);

        // Phase 6: latest service report for this ticket (if any).
        $report = ServiceReport::where('monday_ticket_id', $id)
            ->orderByDesc('created_at')
            ->first();

        return view('tsp.ticket-show', [
            'user'         => $user,
            'ticket'       => $item,
            'messages'     => $messages,
            'notes'        => $notes,
            'timeActive'   => $active && (int) $active->monday_ticket_id === (int) $id
                ? [
                    'id'              => (int) $active->id,
                    'monday_ticket_id'=> (int) $active->monday_ticket_id,
                    'status'          => $active->status,
                    'elapsed_seconds' => (int) $active->elapsedSeconds(),
                    'started_at'      => $active->started_at?->toIso8601String(),
                    'note'            => $active->note,
                ]
                : null,
            'timeTotal'    => $totalSeconds,
            'existingReport' => $report ? [
                'id'                    => (int) $report->id,
                'service_status'        => $report->service_status,
                'service_status_label'  => $report->statusLabel(),
                'author_name'           => $report->user?->name ?? '—',
                'author_role'           => $report->author_role,
                'total_minutes'         => $report->total_minutes,
                'job_done'              => $report->job_done,
                'problem_and_concerns'  => $report->problem_and_concerns,
                'parts_replaced'        => $report->parts_replaced,
                'recommendation'        => $report->recommendation,
                'remarks'               => $report->remarks,
                'login_date'            => $report->login_date?->toDateString(),
                'service_start_at'      => $report->service_start_at?->toDateTimeString(),
                'service_end_at'        => $report->service_end_at?->toDateTimeString(),
                'logout_date'           => $report->logout_date?->toDateString(),
                'call_login_time'       => $report->call_login_time,
                'machine_system'        => $report->machine_system,
                'serial_number'         => $report->serial_number,
                'software_version'      => $report->software_version,
                'contract'              => $report->contract,
                'customer_incharge'     => $report->customer_incharge,
                'customer_incharge_email' => $report->customer_incharge_email,
                'biomed_incharge'       => $report->biomed_incharge,
                'biomed_email'          => $report->biomed_email,
                'created_at'            => $report->created_at->toIso8601String(),
            ] : null,
        ]);
    }

    /**
     * Persist a new internal note and broadcast it on the
     * ticket.{id}.internal private channel. Returns JSON so the
     * Livewire form doesn't re-render the whole page (same pattern
     * as ChatController::send).
     *
     * Side effects, in order:
     *  1. Insert the local `internal_notes` row (with author + role).
     *  2. Mirror the body to the Monday long-text column. This is a
     *     best-effort write; if Monday is unreachable the local row
     *     stays and we re-mirror on the next successful save.
     *  3. Broadcast the event so other TSP tabs see the note live.
     */
    public function store(Request $request, string $id): JsonResponse
    {
        $user = auth()->user();
        $item = $this->loadMondayTicket($id);
        $this->authorizeTicketAccess($user, $item);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $note = InternalNote::create([
            'monday_ticket_id' => $id,
            'user_id'          => $user->id,
            'author_role'      => $user->role,
            'body'             => $data['body'],
        ]);

        // Best-effort mirror to the Monday long-text column. The local
        // row is the source of truth; Monday is a single-value cache
        // of the most recent note.
        try {
            $columnId = config('services.monday.tickets_columns.internal_notes');
            app(MondayClient::class)->writeLongTextColumn(
                (int) $id,
                (string) $columnId,
                $note->body,
            );
            $note->forceFill(['mirrored_to_monday_at' => now()])->save();
        } catch (\Throwable $e) {
            // Don't fail the request — the note is in the local log.
            // The next save will overwrite the column with the latest
            // body, so Monday will catch up automatically.
            Log::warning('Failed to mirror internal note to Monday', [
                'note_id' => $note->id,
                'error'   => $e->getMessage(),
            ]);
        }

        $note->load('user');
        broadcast(new InternalNoteAdded($note))->toOthers();

        return response()->json([
            'ok'          => true,
            'id'          => (int) $note->id,
            'body'        => (string) $note->body,
            'author_name' => (string) ($note->user?->name ?? 'Unknown'),
            'author_role' => (string) $note->author_role,
            'at'          => $note->created_at?->toIso8601String(),
        ]);
    }

    /**
     * Load the internal-note history for a ticket. Plain array shape
     * for the same reason chat messages are plain arrays — Livewire
     * `array` properties choke on Eloquent Collections.
     */
    protected function loadNoteHistory(string $mondayTicketId): array
    {
        return InternalNote::with('user')
            ->where('monday_ticket_id', $mondayTicketId)
            ->orderBy('created_at')
            ->get()
            ->map(function (InternalNote $note) {
                return [
                    'id'          => (int) $note->id,
                    'body'        => (string) $note->body,
                    'author_role' => (string) $note->author_role,
                    'author_name' => (string) ($note->user?->name ?? 'Unknown'),
                    'created_at'  => optional($note->created_at)->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Shared chat-history loader (copy of Tsp\ChatController::loadMessageHistory
     * — kept local so the controllers stay self-contained).
     */
    protected function loadMessageHistory(string $mondayTicketId, \App\Models\User $viewer): array
    {
        return \App\Models\ChatMessage::with('user')
            ->where('monday_ticket_id', $mondayTicketId)
            ->orderBy('created_at')
            ->get()
            ->map(function ($msg) use ($viewer) {
                return [
                    'id'          => (int) $msg->id,
                    'body'        => (string) $msg->body,
                    'sender_role' => (string) $msg->sender_role,
                    'sender_name' => (string) ($msg->user?->name ?? 'Unknown'),
                    'mine'        => (int) $msg->user_id === (int) $viewer->id,
                    'created_at'  => optional($msg->created_at)->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }
}
