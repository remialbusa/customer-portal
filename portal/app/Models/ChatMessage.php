<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    protected $fillable = [
        'monday_ticket_id',
        'user_id',
        'sender_role',
        'body',
        'read_at',
        'mirrored_to_monday_at',
        'monday_update_id',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'mirrored_to_monday_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The channel name for Pusher. One channel per ticket.
     */
    public function channelName(): string
    {
        return 'ticket.' . $this->monday_ticket_id;
    }
}
