<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Sync state of a ServiceReport row with respect to monday.com.
 *
 *   pending   - The TSR was submitted offline (or the monday write failed
 *               last time) and is sitting in the local queue. The
 *               SyncPendingTsrReports drainer will pick it up.
 *
 *   syncing   - The drainer has the row in-flight. Used to make the
 *               "drain now" button idempotent (don't double-process).
 *
 *   synced    - The TSR row exists on the monday TSR board (5029041107)
 *               AND the source ticket's status has been updated.
 *               `monday_tsr_item_id` and `mirrored_to_monday_at` are set.
 *
 *   error     - Last sync attempt failed. `sync_error` has the message.
 *               The drainer retries on the next tick; manual retry is
 *               also available from the TSP's "Pending sync" page.
 *
 *   discarded - The user (or an admin) has given up on this row. It
 *               stays in the local DB for audit purposes but is
 *               excluded from the dashboard count and the drainer
 *               will not pick it up. Use this when the source ticket
 *               was deleted on Monday, or the data is otherwise
 *               unrecoverable, and the user just wants the banner
 *               to stop showing it.
 */
enum SyncState: string
{
    case Pending   = 'pending';
    case Syncing   = 'syncing';
    case Synced    = 'synced';
    case Error     = 'error';
    case Discarded = 'discarded';

    public function label(): string
    {
        return match ($this) {
            self::Pending   => 'Queued for sync',
            self::Syncing   => 'Syncing now…',
            self::Synced    => 'Synced to Monday',
            self::Error     => 'Sync failed',
            self::Discarded => 'Discarded',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending   => 'yellow',
            self::Syncing   => 'blue',
            self::Synced    => 'green',
            self::Error     => 'red',
            self::Discarded => 'gray',
        };
    }

    /**
     * True when the row still needs to be processed by the drainer.
     *
     * Discarded rows are excluded: the user has explicitly given up
     * on them, and we don't want the drainer to keep retrying on
     * every tick. Pending and Error rows are still actionable.
     */
    public function isOutstanding(): bool
    {
        return $this === self::Pending || $this === self::Error;
    }
}
