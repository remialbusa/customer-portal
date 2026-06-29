<?php

declare(strict_types=1);

namespace App\Livewire\Tsp\Tickets;

use App\Actions\SubmitServiceReport;
use App\Enums\ServiceStatus;
use App\Http\Requests\StoreServiceReportRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * The TSR creation form. Owns:
 *   - The form state (matches the DTO shape, snake_case keys).
 *   - The three signature canvases (TSP, customer, biomed).
 *   - The "total minutes" computed field.
 *
 * The form does NOT talk to Monday directly. Submission goes through
 * StoreServiceReportRequest -> TsrSubmissionDto -> SubmitServiceReport
 * action, which writes to the local DB and queues the Monday write
 * for the offline drainer.
 *
 * Tickets are not a local Eloquent model — they live on Monday.com.
 * The component therefore takes the `monday_item_id` string (the
 * "ticket number" the user sees) and passes it to the action. Auth
 * is checked upstream by the controller; this component assumes
 * `Auth::user()` is a TSP.
 */
class CreateServiceReport extends Component
{
    /** Monday item id of the source ticket (the "Ticket #"). */
    public string $ticketNumber = '';

    /** Local id the form was mounted with — sticky across saves. */
    public string $localId = '';

    // Form state -- mirrors StoreServiceReportRequest rules()
    public string $serviceStatus = 'open';
    public string $email = '';
    public string $problemAndConcerns = '';
    public string $jobDone = '';
    public string $partsReplaced = '';
    public string $recommendation = '';
    public string $remarks = '';
    public ?string $logInDate = null;
    public ?string $serviceStartDateTime = null;
    public ?string $serviceEndDateTime = null;
    public ?string $logOutDate = null;
    public string $machineSystemSerialNumber = '';
    public string $softwareVersionNo = '';

    // Signatures
    public string $tspSignatureName = '';
    public string $tspSignatureDataUrl = '';
    public string $customerName = '';
    public string $customerEmail = '';
    public string $customerSignatureDataUrl = '';
    public string $biomedName = '';
    public string $biomedEmail = '';
    public string $biomedSignatureDataUrl = '';

    /** @var array<int, string> */
    public array $tspWorkWith = [];

    /** CSV input from the form, getter/setter maps to $tspWorkWith. */
    public string $tspWorkWithCsv = '';

    // Computed
    public int $totalMinutes = 0;
    public ?string $lastSyncedAt = null;
    public ?string $lastError = null;

    public function mount(string $ticketNumber = ''): void
    {
        $this->ticketNumber = $ticketNumber;
        $this->email = Auth::user()?->email ?? '';

        // Generate a stable local id the first time the form is
        // rendered; the offline layer keeps the same id across
        // saves so retries are idempotent.
        $this->localId = request()->header('X-TSR-Local-Id')
            ?: (string) \Illuminate\Support\Str::uuid();

        $this->serviceStatus = ServiceStatus::Open->value;
    }

    public function updatedServiceStartDateTime(): void
    {
        $this->recomputeTotalMinutes();
    }

    public function updatedServiceEndDateTime(): void
    {
        $this->recomputeTotalMinutes();
    }

    public function updatedTspWorkWithCsv(): void
    {
        $this->tspWorkWith = array_values(array_filter(array_map(
            'trim',
            explode(',', $this->tspWorkWithCsv)
        )));
    }

    protected function recomputeTotalMinutes(): void
    {
        if ($this->serviceStartDateTime && $this->serviceEndDateTime) {
            try {
                $start = \Carbon\CarbonImmutable::parse($this->serviceStartDateTime);
                $end   = \Carbon\CarbonImmutable::parse($this->serviceEndDateTime);
                // diffInMinutes returns a *signed* integer in Carbon 2+.
                // We want the absolute duration in minutes (end - start),
                // never negative.
                $this->totalMinutes = (int) round(abs($end->diffInMinutes($start, true)));
            } catch (\Throwable) {
                $this->totalMinutes = 0;
            }
        }
    }

    public function submit(SubmitServiceReport $action): void
    {
        // Re-run the duration math right before persisting in case the
        // user edited a date without firing updated*() hooks (e.g. via
        // JS calling set()).
        $this->recomputeTotalMinutes();
        $this->lastError = null;

        try {
            $payload = $this->buildPayload();

            // Build a real FormRequest, then run the validator against
            // it manually. Going through a request stub via createFrom()
            // leaves the validator uninitialised ($this->validator is
            // null), which is what produced "Call to a member function
            // validated() on null". We bypass FormRequest altogether
            // and call Validator::make() directly so the rules from
            // StoreServiceReportRequest::rules() remain the single
            // source of truth.
            $rules    = (new StoreServiceReportRequest())->rules();
            $messages = (new StoreServiceReportRequest())->messages();
            $validator = \Illuminate\Support\Facades\Validator::make($payload, $rules, $messages);

            $validated = $validator->validate();
            $dto      = \App\DataTransferObjects\TsrSubmissionDto::fromValidated($validated);

            $action->execute(Auth::user(), $dto);

            $this->lastSyncedAt = now()->toIso8601String();
            session()->flash('tsr.saved', true);

            $this->dispatch('tsr.saved', localId: $this->localId);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->lastError = collect($e->errors())->flatten()->implode(' / ');
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildPayload(): array
    {
        return [
            'local_id'                => $this->localId,
            'ticket_number'           => $this->ticketNumber,
            'client_submitted_at'     => now()->toIso8601String(),
            'service_status'          => $this->serviceStatus,
            'email'                   => $this->email,
            'problem_and_concerns'    => $this->problemAndConcerns,
            'job_done'                => $this->jobDone,
            'parts_replaced'          => $this->partsReplaced,
            'recommendation'          => $this->recommendation,
            'remarks'                 => $this->remarks,
            'log_in_date'             => $this->logInDate,
            'service_start_date_time' => $this->serviceStartDateTime,
            'service_end_date_time'   => $this->serviceEndDateTime,
            'log_out_date'            => $this->logOutDate,
            'machine_system_serial_number' => $this->machineSystemSerialNumber,
            'software_version_no'     => $this->softwareVersionNo,
            'tsp_signature' => [
                'name'      => $this->tspSignatureName,
                'signature' => $this->tspSignatureDataUrl,
            ],
            'customer_in_charge' => [
                'full_name'     => $this->customerName,
                'email_address' => $this->customerEmail,
                'signature'     => $this->customerSignatureDataUrl,
            ],
            'biomed_person_in_charge' => [
                'name'         => $this->biomedName,
                'email_address'=> $this->biomedEmail,
                'signature'    => $this->biomedSignatureDataUrl,
            ],
            'tsp_work_with' => $this->tspWorkWith,
            'total_minutes' => $this->totalMinutes,
        ];
    }

    public function render(): View
    {
        return view('livewire.tsp.tickets.create-service-report');
    }
}
