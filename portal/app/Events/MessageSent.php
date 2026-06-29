<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ChatMessage $message)
    {
    }

    /**
     * Per-ticket private channel. Pusher path: private-ticket.{mondayId}.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('ticket.' . $this->message->monday_ticket_id),
        ];
    }

    /**
     * Name the Pusher event 'message.sent' so the client can listen
     * with channel.listen('.message.sent', ...).
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * Only the bits the client needs.
     */
    public function broadcastWith(): array
    {
        $user = $this->message->user;
        return [
            'id'               => $this->message->id,
            'monday_ticket_id' => $this->message->monday_ticket_id,
            'body'             => $this->message->body,
            'sender_role'      => $this->message->sender_role,
            'sender_name'      => $user?->name ?? 'Unknown',
            'created_at'       => $this->message->created_at?->toIso8601String(),
        ];
    }
}
