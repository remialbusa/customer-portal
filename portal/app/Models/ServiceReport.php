<?php

namespace App\Models;

use App\Enums\ServiceStatus;
use App\Models\Concerns\HasServiceStatusLabel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Service report submitted by a TSP after performing on-site service
 * on a ticket. Mirrored to the EXTERNAL - TSR board (5029041107); one
 * local row per submitted report, full audit trail with author +
 * timestamps.
 *
 * Status semantics: see App\Enums\ServiceStatus.
 * TSR → ticket status mapping: see App\Support\Monday\TsrStatusMapper.
 */
class ServiceReport extends Model
{
    use HasServiceStatusLabel;

    protected $fillable = [
        'monday_ticket_id',
        'monday_service_report_id',
        'user_id',
        'author_role',
        'problem_and_concerns',
        'job_done',
        'parts_replaced',
        'recommendation',
        'remarks',
        'serial_number',
        'software_version',
        'machine_system',
        'contract',
        'customer_incharge',
        'customer_incharge_email',
        'biomed_incharge',
        'biomed_email',
        'tsp_workwith_person_ids',
        'login_date',
        'service_start_at',
        'service_end_at',
        'logout_date',
        'call_login_time',
        'service_status',
        'total_minutes',
        'mirrored_to_monday_at',
        'monday_update_id',
        // Offline-sync (migration 2026_06_21_080000)
        'local_id',
        'client_submitted_at',
        'sync_state',
        'sync_error',
        'monday_tsr_item_id',
        // Signature files (migration 2026_06_21_080500)
        'tsp_signature_path',
        'customer_signature_path',
        'biomed_signature_path',
    ];

    protected $casts = [
        'tsp_workwith_person_ids' => 'array',
        'login_date'              => 'datetime',
        'service_start_at'        => 'datetime',
        'service_end_at'          => 'datetime',
        'logout_date'             => 'datetime',
        'mirrored_to_monday_at'   => 'datetime',
        'client_submitted_at'     => 'datetime',
        'sync_state'              => \App\Enums\SyncState::class,
        'service_status'          => \App\Enums\ServiceStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * All TSRs written for the same source ticket, newest first.
     * Useful on the ticket detail page to show the service history.
     */
    public function siblingsForTicket(): HasMany
    {
        return $this->hasMany(self::class, 'monday_ticket_id', 'monday_ticket_id')
            ->where('id', '!=', $this->id)
            ->latest('created_at');
    }
}

