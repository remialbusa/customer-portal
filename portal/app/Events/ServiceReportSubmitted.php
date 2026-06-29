<?php

namespace App\Events;

use App\Models\ServiceReport;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a TSP submits a new service report. Broadcast on a
 * ticket-scoped private channel — the customer IS subscribed to it
 * (so the customer dashboard / ticket page can refresh in real-time
 * when the TSP changes the ticket's status).
 *
 * The customer only sees a sanitized summary; raw TSP-only fields
 * (problem_and_concerns internals, etc.) are stripped.
 */
class ServiceReportSubmitted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ServiceReport $report)
    {
    }

    public function broadcastOn(): array
    {
        return [
            // Customer-visible: ticket.{id}.customer
            new PrivateChannel('ticket.' . $this->report->monday_ticket_id . '.customer'),
            // TSP/manager visibility
            new PrivateChannel('ticket.' . $this->report->monday_ticket_id . '.internal'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'service-report.submitted';
    }

    public function broadcastWith(): array
    {
        $user = $this->report->user;
        $status = $this->report->service_status;
        $label  = ServiceReport::STATUS_LABELS[$status] ?? ucfirst((string) $status);

        // Customer-safe payload. We deliberately omit the TSP-only
        // operational fields (parts replaced, internal remarks, etc.)
        // and only include the resolution status + a public summary.
        $customerSummary = $this->report->job_done ?: $this->report->remarks ?: null;

        return [
            'id'                  => $this->report->id,
            'monday_ticket_id'    => $this->report->monday_ticket_id,
            'service_status'      => $status,
            'service_status_label'=> $label,
            'author_name'         => $user?->name ?? 'Unknown',
            'author_role'         => $this->report->author_role,
            'total_minutes'       => $this->report->total_minutes,
            'completed_at'        => $this->report->service_end_at?->toIso8601String(),
            'created_at'          => $this->report->created_at?->toIso8601String(),
            'customer_summary'    => $customerSummary,
        ];
    }
}
