<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Internal notes are TSP-only annotations on a ticket. Each note
     * is a separate row (full history), and the latest note's body
     * is mirrored to a dedicated long-text column on the Tickets
     * board in Monday.com (see config('services.monday.tickets_columns')
     * for the column id). The column itself is a single value, so
     * Monday always reflects the most-recent note; the local table
     * is the audit trail.
     */
    public function up(): void
    {
        Schema::create('internal_notes', function (Blueprint $table) {
            $table->id();
            $table->string('monday_ticket_id', 32)->index()
                ->comment('Monday.com item id for the ticket this note belongs to');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('author_role', ['fse', 'its', 'manager', 'admin'])
                ->comment('Role of the author at the time of writing (snapshotted)');
            $table->text('body');
            $table->timestamp('mirrored_to_monday_at')->nullable()
                ->comment('When the latest-note body was last written to the Monday long-text column');
            $table->timestamps();

            $table->index(['monday_ticket_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_notes');
    }
};
