<?php

namespace App\Events;

use App\Models\InternalNote;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a TSP adds an internal note on a ticket. Broadcast on
 * the same private channel family as the customer chat, but on a
 * separate `ticket.{id}.internal` channel that the customer is NOT
 * authorized to subscribe to (see routes/channels.php).
 *
 * The customer-facing app should never see this event.
 */
class InternalNoteAdded implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public InternalNote $note)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('ticket.' . $this->note->monday_ticket_id . '.internal'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'internal-note.added';
    }

    public function broadcastWith(): array
    {
        $user = $this->note->user;
        return [
            'id'               => $this->note->id,
            'monday_ticket_id' => $this->note->monday_ticket_id,
            'body'             => $this->note->body,
            'author_role'      => $this->note->author_role,
            'author_name'      => $user?->name ?? 'Unknown',
            'created_at'       => $this->note->created_at?->toIso8601String(),
        ];
    }
}
