<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 5 — Time tracker.
     *
     * Each time entry is one work session on a Monday ticket by one
     * user. The model supports start / pause / resume / stop:
     *
     *   - start: insert with started_at, status=open, accumulated_seconds=0
     *   - pause: leave started_at unchanged, set status=paused, save
     *            checkpoint_at = now (the time we paused at)
     *   - resume: clear checkpoint_at, status=open (elapsed is computed
     *            from started_at + accumulated_seconds + (now - resumed_at))
     *   - stop:  set stopped_at, status=closed, accumulated_seconds = total
     *
     * A user can have at most ONE open or paused entry at any time
     * (enforced by a unique partial index — see up() notes).
     *
     * On the Monday side, each closed entry is mirrored as a `create_update`
     * (no column writes). The local table is the source of truth for
     * totals and reporting.
     */
    public function up(): void
    {
        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();

            $table->string('monday_ticket_id', 32)->index()
                ->comment('Monday.com item id for the ticket being worked on');

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->enum('status', ['open', 'paused', 'closed'])
                ->comment('open = currently running; paused = can be resumed; closed = finalized');

            $table->timestamp('started_at')
                ->comment('Original start time of the session; never moves on resume');

            $table->timestamp('resumed_at')->nullable()
                ->comment('When the timer was last resumed; null when status != open');

            $table->timestamp('stopped_at')->nullable()
                ->comment('When the timer was stopped/closed');

            $table->unsignedInteger('accumulated_seconds')->default(0)
                ->comment('Total time accumulated across all prior run segments of this entry');

            $table->string('note', 500)->nullable()
                ->comment('Optional one-line summary of the work segment (mirrored to Monday update body)');

            $table->string('monday_update_id', 64)->nullable()
                ->comment('Monday update id once the closed entry is mirrored via create_update');

            $table->timestamp('mirrored_to_monday_at')->nullable();

            $table->timestamps();

            // Find a user's open timer in O(1) by (user_id, status).
            $table->index(['user_id', 'status']);

            // Per-ticket total time queries.
            $table->index(['monday_ticket_id', 'status']);
        });

        // Enforce one-open-or-paused-per-user. SQLite supports unique
        // indexes with WHERE clauses only via raw SQL, so we use a
        // simpler approach: a unique index on user_id where status is
        // in ('open', 'paused'). Because SQLite ignores the where
        // clause, the equivalent is a unique index on (user_id, status)
        // with a check that the entry is open/paused.
        //
        // Laravel's Schema::table doesn't expose partial indexes, so
        // we fall back to enforcing this at the application level
        // (TimeTrackerService::start() / resume() check for an existing
        // open or paused entry and throw if one is found).
    }

    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};
