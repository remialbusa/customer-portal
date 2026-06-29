<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Enums\ServiceStatus;
use Carbon\CarbonImmutable;

/**
 * Validated payload for a TSR submission. Built from
 * StoreServiceReportRequest and consumed by SubmitServiceReport.
 *
 * Naming convention: this DTO uses the EXACT PascalCase + nested-object
 * shape that the TSR form posts (per spec). The Action is the only place
 * that translates these into snake_case DB columns and monday.com
 * column_values JSON.
 *
 * `localId` and `clientSubmittedAt` are filled in by the JavaScript
 * offline layer (Dexie + form submit handler). The server's
 * `created_at` is still the authoritative "received at" timestamp.
 */
final readonly class TsrSubmissionDto
{
    public function __construct(
        // Identity & linkage
        public string $localId,                  // UUID, idempotency key
        public string $ticketNumber,             // Monday ticket id
        public CarbonImmutable $clientSubmittedAt, // device clock

        // Status (5-value TSR enum)
        public ServiceStatus $serviceStatus,

        // Contact
        public string $email,

        // Narrative
        public string $problemAndConcerns,
        public string $jobDone,
        public string $partsReplaced,
        public string $recommendation,
        public string $remarks,

        // Timestamps (all optional — TSP may not have closed out yet)
        public ?CarbonImmutable $logInDate,
        public ?CarbonImmutable $serviceStartDateTime,
        public ?CarbonImmutable $serviceEndDateTime,
        public ?CarbonImmutable $logOutDate,

        // Asset
        public string $machineSystemSerialNumber,
        public string $softwareVersionNo,

        // Signatures (all three required by the validator)
        public SignatureBlob $tspSignature,
        public SignatureBlob $customerInCharge,
        public SignatureBlob $biomedPersonInCharge,

        // Co-TSPs
        /** @var array<int, string> monday person ids */
        public array $tspWorkWith,

        // Computed by the Livewire component (start→end diff).
        public int $totalMinutes,
    ) {
    }

    /**
     * Build from a raw, already-validated array. The FormRequest is
     * responsible for shape; this method just maps the snake-case
     * validator keys onto the camelCase DTO properties.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromValidated(array $data): self
    {
        $sig = static fn (string $key): SignatureBlob => new SignatureBlob(
            name:      (string) ($data[$key]['name'] ?? ''),
            dataUrl:   (string) ($data[$key]['signature'] ?? ''),
        );
        // Customer & BIOMED use a different nested shape in the request
        // (full_name, email_address, signature) than TSP (name, signature).
        $sigWithEmail = static fn (string $key): SignatureBlob => new SignatureBlob(
            name:    (string) ($data[$key]['full_name'] ?? ''),
            dataUrl: (string) ($data[$key]['signature'] ?? ''),
            email:  (string) ($data[$key]['email_address'] ?? ''),
        );

        $ts = static fn (?string $raw): ?CarbonImmutable =>
            $raw === null || $raw === '' ? null : CarbonImmutable::parse($raw);

        return new self(
            localId:               (string) $data['local_id'],
            ticketNumber:          (string) $data['ticket_number'],
            clientSubmittedAt:     CarbonImmutable::parse($data['client_submitted_at']),
            serviceStatus:         ServiceStatus::from((string) $data['service_status']),
            email:                 (string) $data['email'],
            problemAndConcerns:    (string) $data['problem_and_concerns'],
            jobDone:               (string) $data['job_done'],
            partsReplaced:         (string) $data['parts_replaced'],
            recommendation:        (string) $data['recommendation'],
            remarks:               (string) $data['remarks'],
            logInDate:             $ts($data['log_in_date'] ?? null),
            serviceStartDateTime:  $ts($data['service_start_date_time'] ?? null),
            serviceEndDateTime:    $ts($data['service_end_date_time'] ?? null),
            logOutDate:            $ts($data['log_out_date'] ?? null),
            machineSystemSerialNumber: (string) $data['machine_system_serial_number'],
            softwareVersionNo:     (string) $data['software_version_no'],
            tspSignature:          $sig('tsp_signature'),
            customerInCharge:      $sigWithEmail('customer_in_charge'),
            biomedPersonInCharge:  $sigWithEmail('biomed_person_in_charge'),
            tspWorkWith:           array_values(array_filter(
                (array) ($data['tsp_work_with'] ?? []),
                static fn ($v) => is_string($v) && $v !== '',
            )),
            totalMinutes:          (int) ($data['total_minutes'] ?? 0),
        );
    }
}
