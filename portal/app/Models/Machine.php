<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Machine extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nickname',
        'brand',
        'model',
        'serial_number',
        'installation_date',
        'notes',
        'is_primary',
        'monday_id',
    ];

    protected function casts(): array
    {
        return [
            'installation_date' => 'date',
            'is_primary'        => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Human-friendly label used in dropdowns and list rows.
     */
    protected function displayLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => trim(collect([
                $this->nickname,
                $this->brand.' '.$this->model,
            ])->filter()->implode(' — ')) ?: 'Unnamed machine',
        );
    }

    /**
     * Primary machine for a given user, or null if none.
     */
    public static function primaryFor(int $userId): ?self
    {
        return static::query()
            ->where('user_id', $userId)
            ->where('is_primary', true)
            ->first();
    }
}
