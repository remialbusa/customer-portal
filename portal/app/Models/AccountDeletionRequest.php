<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A user's request to have their account deleted.
 *
 * Self-deletion is intentionally not allowed for anyone except the
 * superadmin: customers (and TSPs) must file a request that a
 * superadmin reviews and approves. This protects against accidental
 * loss of ticket history, audit trails, and chat logs.
 *
 * The request is intentionally decoupled from the user row: we
 * snapshot the email + name at creation time so the request can
 * outlive the user once the superadmin approves and deletes the
 * account.
 */
class AccountDeletionRequest extends Model
{
    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'email',
        'name',
        'role',
        'reason',
        'status',
        'processed_by',
        'processed_at',
        'decision_note',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Convenience: most-recent pending request for a given user.
     * Returns null if none exists. Used by the profile page to
     * detect "you already have a pending request" and avoid
     * duplicates.
     */
    public static function latestPendingFor(int $userId): ?self
    {
        return static::query()
            ->where('user_id', $userId)
            ->where('status', self::STATUS_PENDING)
            ->latest('id')
            ->first();
    }
}
