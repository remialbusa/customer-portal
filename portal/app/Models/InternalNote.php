<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One TSP-only annotation on a Monday ticket. Internal notes are
 * stored in a dedicated long-text column on the Tickets board
 * (the column holds the latest note body — Monday has no notion
 * of an append-only log for plain text), but the local table keeps
 * the full history with author + timestamp.
 */
class InternalNote extends Model
{
    protected $fillable = [
        'monday_ticket_id',
        'user_id',
        'author_role',
        'body',
        'mirrored_to_monday_at',
    ];

    protected $casts = [
        'mirrored_to_monday_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
