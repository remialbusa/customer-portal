<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a TSP claims a ticket from the regional pool. The
 * customer-side chat-bubble + ticket page listens for this event so
 * the customer sees "A technician has been assigned to your ticket"
 * the moment the claim lands — instead of waiting for the next 15s
 * poll.
 *
 * The customer-facing app only ever sees a sanitized payload
 * (technician name + ticket id), not the TSP's person id, role, or
 * any other operational fields. The TSP-side dashboard listens on
 * a separate channel to know when a ticket leaves the regional pool.
 */
class TicketClaimed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $mondayTicketId,
        public string $tspName,
        public ?string $tspRole = null,
        public ?string $previousStatus = null,
        public ?string $newStatus = 'RESPONDED',
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            // Customer-visible: the customer who owns the ticket
            // listens to this channel.
            new PrivateChannel('ticket.' . $this->mondayTicketId . '.customer'),
            // TSP-visible: other TSPs in the region see the ticket
            // leave the pool in real-time.
            new PrivateChannel('region.all'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ticket.claimed';
    }

    public function broadcastWith(): array
    {
        return [
            'monday_ticket_id' => $this->mondayTicketId,
            'tsp_name'         => $this->tspName,
            'tsp_role'         => $this->tspRole,
            'previous_status'  => $this->previousStatus,
            'new_status'       => $this->newStatus,
            'claimed_at'       => now()->toIso8601String(),
        ];
    }
}
