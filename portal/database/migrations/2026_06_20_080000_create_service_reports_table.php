<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Service reports written by TSPs after performing on-site service.
     * One report per TSP-visit to a ticket; the latest report's status
     * drives the ticket's status on the customer-facing Tickets board.
     *
     * Mirrored to the EXTERNAL - TSR board (5029041107) — a separate
     * board with a board_relation link back to the source ticket and
     * its own "Service Status" (OPEN / IN-PROGRESS / PENDING /
     * ESCALATED / COMPLETED). The local table is the audit trail;
     * Monday is the operational record.
     */
    public function up(): void
    {
        Schema::create('service_reports', function (Blueprint $table) {
            $table->id();
            $table->string('monday_ticket_id', 32)->index()
                ->comment('Monday.com item id of the source ticket');

            $table->string('monday_service_report_id', 32)->nullable()
                ->comment('Monday.com item id of the corresponding TSR row (board 5029041107)');

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()
                ->comment('TSP who wrote the report');

            $table->enum('author_role', ['fse', 'its', 'manager', 'admin']);

            // ── Form fields captured by the TSP ──────────────────────
            $table->text('problem_and_concerns')->nullable();
            $table->text('job_done')->nullable();
            $table->text('parts_replaced')->nullable();
            $table->text('recommendation')->nullable();
            $table->text('remarks')->nullable();

            $table->string('serial_number', 200)->nullable();
            $table->string('software_version', 200)->nullable();
            $table->string('machine_system', 100)->nullable()
                ->comment('UC-3500 / UF-4000 / etc., or N/A');
            $table->enum('contract', ['Purchased', 'RTU', 'Demo', 'Backup'])->nullable();

            // Customer / biomed on-site contacts
            $table->string('customer_incharge', 200)->nullable();
            $table->string('customer_incharge_email', 200)->nullable();
            $table->string('biomed_incharge', 200)->nullable();
            $table->string('biomed_email', 200)->nullable();

            // TSP WORKWITH — comma-separated list of additional TSPs
            $table->json('tsp_workwith_person_ids')->nullable();

            // Date / time fields. Stored as strings so we can hold either
            // date-only (Y-m-d) or date+time (Y-m-d H:i) depending on the
            // Monday column. We keep them as datetime where possible.
            $table->timestamp('login_date')->nullable();
            $table->timestamp('service_start_at')->nullable();
            $table->timestamp('service_end_at')->nullable();
            $table->timestamp('logout_date')->nullable();
            $table->string('call_login_time', 8)->nullable()->comment('HH:MM:SS');

            // ── Resolution state ────────────────────────────────────
            $table->enum('service_status', [
                'open', 'in_progress', 'pending', 'escalated', 'completed',
            ])->default('open');

            $table->integer('total_minutes')->nullable()
                ->comment('Auto-aggregated from time_entries for the source ticket at submit time');

            // ── Mirror bookkeeping ──────────────────────────────────
            $table->timestamp('mirrored_to_monday_at')->nullable();
            $table->string('monday_update_id', 64)->nullable()
                ->comment('id of the create_update entry posted to the source ticket');

            $table->timestamps();

            $table->index(['monday_ticket_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_reports');
    }
};
