<?php

declare(strict_types=1);

namespace App\Actions;

use App\DataTransferObjects\TsrSubmissionDto;
use App\Enums\SyncState;
use App\Models\ServiceReport;
use App\Models\User;
use App\Services\SignatureStorage;
use App\Support\Monday\TsrStatusMapper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Single entry point for a TSP submitting a TSR. Idempotent on
 * `local_id` so offline retries never create duplicate rows.
 *
 * Phases:
 *   1. Find-or-create the ServiceReport row by local_id.
 *   2. Persist the three signatures to local storage.
 *   3. Update DB columns from the DTO.
 *   4. Compute the ticket-side status change via TsrStatusMapper.
 *      (Actual monday write is queued; see SyncPendingTsrReports.)
 *   5. Set sync_state = 'pending' so the drainer picks it up.
 *
 * No monday.com write happens here. The portal's local DB is the
 * authoritative record at submit time; the drainer (next file) is
 * the one that talks to monday.
 */
class SubmitServiceReport
{
    public function __construct(
        protected SignatureStorage $signatures,
    ) {
    }

    public function execute(User $tsp, TsrSubmissionDto $dto): ServiceReport
    {
        return DB::transaction(function () use ($tsp, $dto) {
            // Phase 1: idempotent row
            $report = ServiceReport::firstOrNew(['local_id' => $dto->localId]);

            // First-write-wins: if a synced row already exists for this
            // local_id, return it as-is (no overwrite, no duplicate).
            if ($report->exists && $report->sync_state === SyncState::Synced) {
                return $report;
            }

            // Phase 2: signatures to local storage
            $tspSigPath  = $this->signatures->store($dto->tspSignature,         $dto->localId, 'tsp');
            $custSigPath = $this->signatures->store($dto->customerInCharge,     $dto->localId, 'customer');
            $biomedPath  = $this->signatures->store($dto->biomedPersonInCharge, $dto->localId, 'biomed');

            // Phase 3: fill the DB columns
            $report->fill([
                'monday_ticket_id'        => $dto->ticketNumber,
                'user_id'                 => $tsp->id,
                'author_role'             => $tsp->role,
                'client_submitted_at'     => $dto->clientSubmittedAt,
                'service_status'          => $dto->serviceStatus->value,
                'problem_and_concerns'    => $dto->problemAndConcerns,
                'job_done'                => $dto->jobDone,
                'parts_replaced'          => $dto->partsReplaced,
                'recommendation'          => $dto->recommendation,
                'remarks'                 => $dto->remarks,
                'login_date'              => $dto->logInDate,
                'service_start_at'        => $dto->serviceStartDateTime,
                'service_end_at'          => $dto->serviceEndDateTime,
                'logout_date'             => $dto->logOutDate,
                'serial_number'           => $dto->machineSystemSerialNumber,
                'software_version'        => $dto->softwareVersionNo,
                'customer_incharge'       => $dto->customerInCharge->name,
                'customer_incharge_email' => $dto->customerInCharge->email,
                'biomed_incharge'         => $dto->biomedPersonInCharge->name,
                'biomed_email'            => $dto->biomedPersonInCharge->email,
                'tsp_workwith_person_ids' => $dto->tspWorkWith,
                'total_minutes'           => $dto->totalMinutes,
                'tsp_signature_path'      => $tspSigPath,
                'customer_signature_path' => $custSigPath,
                'biomed_signature_path'   => $biomedPath,
                'sync_state'              => SyncState::Pending,
                'sync_error'              => null,
            ]);
            $report->save();

            // Phase 4: log the would-be ticket status change for the
            // drainer. We don't write to monday here.
            $patch = TsrStatusMapper::toTicketChange($dto->serviceStatus);
            Log::info('TSR submission queued for sync', [
                'local_id'        => $dto->localId,
                'ticket'          => $dto->ticketNumber,
                'tsr_status'      => $dto->serviceStatus->value,
                'ticket_patch'    => $patch,
            ]);

            return $report;
        });
    }
}
