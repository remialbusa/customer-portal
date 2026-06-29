<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->string('monday_ticket_id', 32)->index()
                ->comment('Monday.com item id for the ticket this message belongs to');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('sender_role', ['customer', 'fse', 'its', 'manager', 'admin', 'system'])
                ->comment('Role of the sender at the time of send (snapshotted, not a FK)');
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('mirrored_to_monday_at')->nullable()
                ->comment('When this message was posted to Monday as a ticket update');
            $table->string('monday_update_id', 32)->nullable()
                ->comment('Monday.com update id created by the mirror job');
            $table->timestamps();

            $table->index(['monday_ticket_id', 'created_at']);
            $table->index(['user_id', 'read_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
