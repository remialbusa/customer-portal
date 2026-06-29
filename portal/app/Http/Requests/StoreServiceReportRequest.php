<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DataTransferObjects\TsrSubmissionDto;
use App\Enums\ServiceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates a TSR submission. The Livewire form posts this same shape
 * (snake_case keys, with the nested objects the user spec'd).
 *
 * Key choices:
 *   - `local_id` is required and UUID — the offline JS layer generates
 *     it on first save (so even a half-typed form has an id and can
 *     be resumed).
 *   - All three signatures are required. A customer signature is
 *     mandatory for a TSR to be legally valid per the business rule.
 *   - The `data_url` regex is intentionally loose; the SignatureBlob
 *     value object does the real mime/bytes check downstream.
 *   - Status is restricted to the 5-value ServiceStatus enum.
 *   - Timestamps are nullable: a TSP may submit a "first half" TSR
 *     (login + start) and a "second half" TSR (end + signature) — the
 *     server treats them as separate reports and the Action stitches
 *     them by ticket + local_id.
 */
class StoreServiceReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization (TSP assigned to this ticket) is enforced by
        // TicketPolicy::submitTsr in the controller, not here. This
        // request only validates the shape of the payload.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'local_id'               => ['required', 'uuid'],
            'ticket_number'          => ['required', 'string', 'max:32'],
            'client_submitted_at'    => ['required', 'date'],
            'service_status'         => ['required', Rule::in(array_column(ServiceStatus::cases(), 'value'))],
            'email'                  => ['required', 'email', 'max:200'],

            'problem_and_concerns'   => ['nullable', 'string', 'max:5000'],
            'job_done'               => ['nullable', 'string', 'max:5000'],
            'parts_replaced'         => ['nullable', 'string', 'max:5000'],
            'recommendation'         => ['nullable', 'string', 'max:5000'],
            'remarks'                => ['nullable', 'string', 'max:5000'],

            'log_in_date'            => ['nullable', 'date'],
            'service_start_date_time'=> ['nullable', 'date'],
            'service_end_date_time'  => ['nullable', 'date'],
            'log_out_date'           => ['nullable', 'date'],

            'machine_system_serial_number' => ['nullable', 'string', 'max:200'],
            'software_version_no'    => ['nullable', 'string', 'max:200'],

            'tsp_signature.name'          => ['required', 'string', 'max:200'],
            'tsp_signature.signature'     => ['required', 'string', 'regex:#^data:image/(png|jpeg);base64,[A-Za-z0-9+/=]+$#'],

            // Customer signature is REQUIRED for a valid TSR.
            'customer_in_charge.full_name'    => ['required', 'string', 'max:200'],
            'customer_in_charge.email_address'=> ['required', 'email', 'max:200'],
            'customer_in_charge.signature'    => ['required', 'string', 'regex:#^data:image/(png|jpeg);base64,[A-Za-z0-9+/=]+$#'],

            'biomed_person_in_charge.name'         => ['required', 'string', 'max:200'],
            'biomed_person_in_charge.email_address' => ['required', 'email', 'max:200'],
            'biomed_person_in_charge.signature'    => ['required', 'string', 'regex:#^data:image/(png|jpeg);base64,[A-Za-z0-9+/=]+$#'],

            'tsp_work_with'              => ['nullable', 'array'],
            'tsp_work_with.*'            => ['string', 'max:32'],

            // Computed by the Livewire component (start -> end diff).
            // Without a rule Laravel's validate() drops the key, which
            // is why this used to silently land as 0 in the DB.
            'total_minutes'             => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'tsp_signature.signature.required'         => 'TSP signature is required.',
            'customer_in_charge.signature.required'     => 'Customer signature is required. A TSR without a customer signature is not valid.',
            'biomed_person_in_charge.signature.required'=> 'BIOMED signature is required.',
        ];
    }

    public function toDto(): TsrSubmissionDto
    {
        return TsrSubmissionDto::fromValidated($this->validated());
    }
}
