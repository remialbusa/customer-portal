<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('account_deletion_requests', function (Blueprint $table) {
            $table->id();

            // The user asking to be deleted (or whose account is
            // being requested for deletion on their behalf by an
            // admin). Nullable so a superadmin can pre-create a
            // request for a customer who's locked out.
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Snapshot of the user's identity at request time.
            // We snapshot instead of just joining because the
            // user row may be deleted on approval.
            $table->string('email', 191)->index();
            $table->string('name', 191)->nullable();
            $table->string('role', 32)->nullable();

            // Free-text reason from the requester ("I'm leaving
            // the company", "duplicate account", etc.) — also
            // visible to the superadmin when deciding.
            $table->text('reason')->nullable();

            // pending | approved | rejected | cancelled
            $table->string('status', 16)->default('pending')->index();

            // Superadmin who actioned the request. Nullable while
            // the request is still pending.
            $table->foreignId('processed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->text('decision_note')->nullable();

            $table->timestamps();

            // Common query: "show me all pending requests newest
            // first" — index covers it.
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_deletion_requests');
    }
};
