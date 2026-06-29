<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired whenever a ticket's status95 is updated from the portal
 * (today: as a side effect of a service report submission). The
 * customer dashboard subscribes per-ticket to refresh its row state
 * in real time.
 */
class TicketStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $mondayTicketId,
        public ?string $previousStatus,
        public ?string $newStatus,
        public ?string $resolutionDate = null,
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('ticket.' . $this->mondayTicketId . '.customer'),
            new PrivateChannel('ticket.' . $this->mondayTicketId . '.internal'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ticket.status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'monday_ticket_id' => $this->mondayTicketId,
            'previous_status'  => $this->previousStatus,
            'new_status'       => $this->newStatus,
            'resolution_date'  => $this->resolutionDate,
        ];
    }
}
