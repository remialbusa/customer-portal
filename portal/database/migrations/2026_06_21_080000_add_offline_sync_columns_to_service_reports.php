<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the columns the offline-first TSR submission flow needs.
     *
     *   local_id              - UUID generated on the client at submit time.
     *                           Used as the idempotency key: re-submitting
     *                           the same DTO (e.g. browser crash, retry)
     *                           matches the existing row and does not
     *                           create a duplicate ServiceReport.
     *
     *   client_submitted_at   - The TSP's device clock at the moment they
     *                           tapped Submit. Stored separately from
     *                           created_at so we can show "Submitted from
     *                           device at 14:03 (offline 2h 11m)".
     *
     *   sync_state            - 'pending' / 'syncing' / 'synced' / 'error'.
     *                           Drives the "queued" badge on the ticket
     *                           detail page and the drainer cron.
     *
     *   sync_error            - Last sync error message (NULL on success).
     *                           Cleared on the next successful drain.
     *
     *   monday_tsr_item_id    - The id of the TSR row on board 5029041107
     *                           after a successful sync. NULL while pending.
     */
    public function up(): void
    {
        Schema::table('service_reports', function (Blueprint $table) {
            $table->uuid('local_id')->nullable()->after('id')
                ->comment('Client-generated UUID, idempotency key for offline retry');

            $table->timestamp('client_submitted_at')->nullable()->after('user_id')
                ->comment('TSP device clock at submit time (may be in the past if offline)');

            $table->enum('sync_state', ['pending', 'syncing', 'synced', 'error'])
                ->default('synced')->after('mirrored_to_monday_at')
                ->comment('Offline-sync state. synced = monday write completed.');

            $table->text('sync_error')->nullable()->after('sync_state')
                ->comment('Last sync error message; cleared on next successful drain');

            $table->string('monday_tsr_item_id', 32)->nullable()->after('monday_service_report_id')
                ->comment('id of the TSR item on board 5029041107 after sync');

            $table->unique('local_id');
            $table->index(['sync_state', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('service_reports', function (Blueprint $table) {
            $table->dropUnique(['local_id']);
            $table->dropIndex(['sync_state', 'created_at']);
            $table->dropColumn([
                'local_id', 'client_submitted_at', 'sync_state',
                'sync_error', 'monday_tsr_item_id',
            ]);
        });
    }
};
