<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One work session on a Monday ticket by one user.
 *
 * Status lifecycle:
 *   open   → resumed_at is set; total elapsed = (now - resumed_at) + accumulated_seconds
 *   paused → resumed_at is null; total elapsed = accumulated_seconds
 *   closed → stopped_at is set; total elapsed = stopped_at - started_at - sum(prior paused gaps)
 *
 * The simpler mental model for callers is `elapsedSeconds()` which
 * computes the right thing based on the current status.
 */
class TimeEntry extends Model
{
    protected $fillable = [
        'monday_ticket_id',
        'user_id',
        'status',
        'started_at',
        'resumed_at',
        'stopped_at',
        'accumulated_seconds',
        'note',
        'monday_update_id',
        'mirrored_to_monday_at',
    ];

    protected $casts = [
        'started_at'           => 'datetime',
        'resumed_at'           => 'datetime',
        'stopped_at'           => 'datetime',
        'mirrored_to_monday_at'=> 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /**
     * Total elapsed seconds at $now. Pure math, no DB access.
     */
    public function elapsedSeconds(?\DateTimeInterface $now = null): int
    {
        $now = $now ?? now();

        if ($this->isClosed()) {
            // closed: use stored accumulated_seconds (set at stop time)
            return (int) $this->accumulated_seconds;
        }

        if ($this->isPaused()) {
            return (int) $this->accumulated_seconds;
        }

        // open: add the current run segment
        $segmentSeconds = $this->resumed_at
            ? $this->resumed_at->diffInSeconds($now)
            : $this->started_at->diffInSeconds($now);

        return (int) $this->accumulated_seconds + $segmentSeconds;
    }

    /**
     * Human-readable elapsed time (e.g. "1h 23m 04s" or "23m 04s" or "0:04").
     * Used by the live ticker and the dashboard widget.
     */
    public function elapsedFormatted(?\DateTimeInterface $now = null): string
    {
        $seconds = $this->elapsedSeconds($now);
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        $s = $seconds % 60;

        if ($h > 0) {
            return sprintf('%dh %02dm %02ds', $h, $m, $s);
        }
        return sprintf('%dm %02ds', $m, $s);
    }

    /**
     * Body for the Monday update that mirrors this entry.
     * Plain text, no HTML.
     */
    public function mondayUpdateBody(): string
    {
        $user = $this->user;
        $name = $user?->name ?? 'Unknown';
        $role = $user?->role ?? 'tsp';
        $mins = (int) round($this->elapsedSeconds() / 60);
        $note = trim((string) $this->note);

        $line = sprintf(
            '⏱ %s logged %dm on this ticket',
            $name,
            $mins,
        );
        if ($note !== '') {
            $line .= ' — ' . $note;
        }
        $line .= sprintf(' [%s]', strtoupper($role));
        return $line;
    }
}
