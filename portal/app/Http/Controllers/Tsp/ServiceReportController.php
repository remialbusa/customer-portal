<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tsp;

use App\Actions\SubmitServiceReport;
use App\Actions\SyncPendingTsrReports;
use App\Http\Controllers\Concerns\AssertsTicketAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceReportRequest;
use App\Models\ServiceReport;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Service reports (TSR) — Phase 6 (offline-first).
 *
 * The form is a Livewire component on the page; this controller is
 * the JSON endpoint the Livewire submit AND the offline JS queue both
 * post to. It does NOT talk to Monday directly — that is the
 * drainer's job (see SyncPendingTsrReports).
 *
 * The previous synchronous version of this controller has been
 * replaced: the old `MondayClient::createServiceReportItem` call
 * has been moved to the drainer. The drainer runs:
 *   - on the `online` window event (the JS posts to `sync()`)
 *   - on `schedule:run` every 5 minutes
 *   - manually from the TSP's "pending sync" badge
 */
class ServiceReportController extends Controller
{
    use AssertsTicketAccess;

    /**
     * Read-only detail view of a single service report.
     */
    public function show(int $id): View
    {
        $report = ServiceReport::with('user')->findOrFail($id);
        $this->authorizeTicketAccess(auth()->user(), $this->loadMondayTicket($report->monday_ticket_id));

        return view('tsp.service-report-show', [
            'report' => $report,
            'user'   => auth()->user(),
        ]);
    }

    /**
     * Persist a new service report. Always returns 200 if the payload
     * is valid (even when monday sync is queued for later) so the
     * offline JS queue can safely retry on 5xx without flooding the
     * log with 4xx.
     */
    public function store(
        StoreServiceReportRequest $request,
        string $id,
        SubmitServiceReport $action,
    ): JsonResponse|RedirectResponse {
        $user = auth()->user();
        $item = $this->loadMondayTicket($id);
        $this->authorizeTicketAccess($user, $item);

        // Auto-aggregate total minutes from time_entries on this ticket
        // by anyone. The local table is the audit log so we sum across
        // all users — even if multiple TSPs worked the ticket.
        $totalSeconds = (int) TimeEntry::where('monday_ticket_id', $id)
            ->where('status', 'closed')
            ->sum('accumulated_seconds');
        $totalMinutes = $totalSeconds > 0 ? (int) round($totalSeconds / 60) : null;

        $dto = $request->toDto();

        $report = $action->execute($user, $dto);
        $report->total_minutes = $totalMinutes;
        $report->save();

        if ($request->wantsJson() || $request->isJson()) {
            return response()->json([
                'ok'          => true,
                'local_id'    => $report->local_id,
                'id'          => (int) $report->id,
                'sync_state'  => $report->sync_state?->value,
                'total_minutes' => $totalMinutes,
                'redirect'    => route('tsp.tickets.show', ['id' => $id]),
            ], 200);
        }

        return redirect()
            ->route('tsp.tickets.show', ['id' => $id])
            ->with('tsr.saved', true);
    }

    /**
     * Offline drainer entrypoint. The JS layer POSTs here on the
     * `online` event. Returns the sync stats so the badge component
     * can re-render its counts.
     */
    public function sync(SyncPendingTsrReports $drain): JsonResponse
    {
        $stats = $drain->execute();

        return response()->json(['ok' => true] + $stats, 200);
    }

    /**
     * Read-only sync state for a single ticket. The form's sticky
     * bar polls this every 5s so the "Queued / Syncing / Synced /
     * Error" pill updates in real time without a full page reload.
     *
     * Returns:
     *   { "pending": N, "syncing": N, "synced": N, "error": N,
     *     "last_error": "...", "last_synced_at": "iso8601" }
     *
     * Never throws — the bar is purely cosmetic and we don't want
     * a transient 500 to blank the page.
     */
    public function status(string $id): JsonResponse
    {
        try {
            $counts = ServiceReport::query()
                ->where('monday_ticket_id', $id)
                ->selectRaw('sync_state, COUNT(*) as c')
                ->groupBy('sync_state')
                ->pluck('c', 'sync_state')
                ->toArray();

            $lastErrored = ServiceReport::query()
                ->where('monday_ticket_id', $id)
                ->where('sync_state', \App\Enums\SyncState::Error->value)
                ->orderByDesc('updated_at')
                ->first(['sync_error', 'updated_at']);

            $lastSynced = ServiceReport::query()
                ->where('monday_ticket_id', $id)
                ->where('sync_state', \App\Enums\SyncState::Synced->value)
                ->orderByDesc('mirrored_to_monday_at')
                ->first(['mirrored_to_monday_at']);

            return response()->json([
                'ok'              => true,
                'pending'         => (int) ($counts[\App\Enums\SyncState::Pending->value] ?? 0),
                'syncing'         => (int) ($counts[\App\Enums\SyncState::Syncing->value] ?? 0),
                'synced'          => (int) ($counts[\App\Enums\SyncState::Synced->value]  ?? 0),
                'error'           => (int) ($counts[\App\Enums\SyncState::Error->value]   ?? 0),
                'last_error'      => $lastErrored?->sync_error,
                'last_synced_at'  => optional($lastSynced?->mirrored_to_monday_at)?->toIso8601String(),
            ], 200);
        } catch (\Throwable $e) {
            Log::warning('TSR status fetch failed', [
                'ticket' => $id,
                'error'  => $e->getMessage(),
            ]);
            return response()->json([
                'ok'      => false,
                'error'   => $e->getMessage(),
            ], 200);
        }
    }
}
