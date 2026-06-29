<?php

namespace App\Services;

use App\Exceptions\ExistingTimerException;
use App\Exceptions\TicketNotFoundException;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * State machine for TimeEntry. All transitions go through this class
 * so the "one open or paused per user" invariant is enforced in one place.
 *
 * Invariants:
 *   - A user has at most one entry with status in (open, paused) at a time.
 *   - start() throws if the user already has an open/paused entry on
 *     a DIFFERENT ticket. They can still have closed entries on that
 *     other ticket.
 *   - pause(), resume(), stop() require that the entry is in the right
 *     prior state — no double-pausing, no resuming a closed entry.
 *   - On stop(), the entry's accumulated_seconds is finalized and the
 *     Monday mirror is attempted (best-effort; failure is logged but
 *     does not block the stop).
 */
class TimeTracker
{
    public function __construct(
        protected MondayClient $monday,
    ) {
    }

    /**
     * Open a new timer for $user on $ticketId. If the user already
     * has an open/paused entry on a different ticket, throws
     * ExistingTimerException.
     *
     * @throws ExistingTimerException
     * @throws TicketNotFoundException
     */
    public function start(User $user, int $mondayTicketId, ?string $note = null): TimeEntry
    {
        $this->assertTicketExists($mondayTicketId);

        $existing = $this->activeEntryFor($user);
        if ($existing) {
            if ((int) $existing->monday_ticket_id === $mondayTicketId) {
                // Already on the same ticket. Idempotent: return the
                // existing entry so the caller can keep using it.
                return $existing;
            }
            throw new ExistingTimerException($existing);
        }

        return TimeEntry::create([
            'monday_ticket_id'    => (string) $mondayTicketId,
            'user_id'             => $user->id,
            'status'              => 'open',
            'started_at'          => now(),
            'resumed_at'          => now(),
            'accumulated_seconds' => 0,
            'note'                => $note,
        ]);
    }

    /**
     * Pause an open entry. Updates accumulated_seconds to include
     * the current run segment, sets status to paused, clears resumed_at.
     */
    public function pause(TimeEntry $entry): TimeEntry
    {
        $this->assertOwnedByAuth($entry);
        if (! $entry->isOpen()) {
            throw new RuntimeException("Entry #{$entry->id} is not open (status={$entry->status}).");
        }

        $entry->accumulated_seconds = $entry->elapsedSeconds();
        $entry->resumed_at = null;
        $entry->status = 'paused';
        $entry->save();
        return $entry;
    }

    /**
     * Resume a paused entry. Sets status to open and resumed_at to now.
     */
    public function resume(TimeEntry $entry): TimeEntry
    {
        $this->assertOwnedByAuth($entry);
        if (! $entry->isPaused()) {
            throw new RuntimeException("Entry #{$entry->id} is not paused (status={$entry->status}).");
        }

        $entry->status = 'open';
        $entry->resumed_at = now();
        $entry->save();
        return $entry;
    }

    /**
     * Stop an entry (open or paused). Finalizes accumulated_seconds,
     * sets stopped_at, sets status=closed, and tries to mirror to Monday.
     *
     * Returns the closed entry. The Monday update id is set on the
     * entry if the mirror succeeded; otherwise `mirrored_to_monday_at`
     * stays null and a warning is logged.
     */
    public function stop(TimeEntry $entry): TimeEntry
    {
        $this->assertOwnedByAuth($entry);
        if ($entry->isClosed()) {
            throw new RuntimeException("Entry #{$entry->id} is already closed.");
        }

        $entry->accumulated_seconds = $entry->elapsedSeconds();
        $entry->stopped_at = now();
        $entry->resumed_at = null;
        $entry->status = 'closed';
        $entry->save();

        $this->mirrorToMonday($entry);

        return $entry;
    }

    /**
     * Get the user's currently-active entry (open or paused), if any.
     */
    public function activeEntryFor(User $user): ?TimeEntry
    {
        return TimeEntry::where('user_id', $user->id)
            ->whereIn('status', ['open', 'paused'])
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Get a user's closed entries for a ticket, in chronological order.
     * Used by the ticket-detail page to show "Total time on this ticket".
     */
    public function closedEntriesForTicket(int $mondayTicketId, ?int $userId = null)
    {
        $q = TimeEntry::where('monday_ticket_id', (string) $mondayTicketId)
            ->where('status', 'closed')
            ->orderBy('started_at');

        if ($userId !== null) {
            $q->where('user_id', $userId);
        }

        return $q->get();
    }

    /**
     * Total seconds logged against a ticket (closed entries only, all users).
     */
    public function totalSecondsForTicket(int $mondayTicketId): int
    {
        return (int) TimeEntry::where('monday_ticket_id', (string) $mondayTicketId)
            ->where('status', 'closed')
            ->sum('accumulated_seconds');
    }

    /**
     * Total seconds logged by a user today (closed entries whose
     * started_at is on the current calendar day).
     */
    public function todaySecondsForUser(User $user): int
    {
        return (int) TimeEntry::where('user_id', $user->id)
            ->where('status', 'closed')
            ->whereDate('started_at', today())
            ->sum('accumulated_seconds');
    }

    // ---------------------------------------------------------------------
    // Internals
    // ---------------------------------------------------------------------

    protected function assertTicketExists(int $mondayTicketId): void
    {
        $item = $this->monday->getItem($mondayTicketId);
        if (! $item) {
            throw new TicketNotFoundException("Ticket {$mondayTicketId} not found in Monday.com.");
        }
    }

    protected function assertOwnedByAuth(TimeEntry $entry): void
    {
        $user = auth()->user();
        if (! $user || (int) $entry->user_id !== (int) $user->id) {
            throw new RuntimeException("Entry #{$entry->id} is not owned by the current user.");
        }
    }

    protected function mirrorToMonday(TimeEntry $entry): void
    {
        try {
            $updateId = $this->monday->createUpdate(
                (int) $entry->monday_ticket_id,
                $entry->mondayUpdateBody(),
            );
            $entry->monday_update_id = $updateId;
            $entry->mirrored_to_monday_at = now();
            $entry->save();
        } catch (\Throwable $e) {
            Log::warning('TimeTracker: Monday mirror failed', [
                'entry_id'   => $entry->id,
                'ticket_id'  => $entry->monday_ticket_id,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
