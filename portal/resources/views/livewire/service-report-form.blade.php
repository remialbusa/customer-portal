<?php

use Livewire\Volt\Component;

new class extends Component
{
    public int $ticketId;
    public string $currentUserName;
    public string $currentUserRole;
    public ?string $currentUserEmail = null;

    /**
     * Existing report for this ticket (if any). When present, the
     * form is rendered as a read-only summary block.
     *
     * @var array{id:int,service_status:string,service_status_label:string,author_name:string,author_role:string,total_minutes:?int,job_done:?string,problem_and_concerns:?string,parts_replaced:?string,recommendation:?string,remarks:?string,login_date:?string,service_start_at:?string,service_end_at:?string,logout_date:?string,call_login_time:?string,machine_system:?string,serial_number:?string,software_version:?string,contract:?string,customer_incharge:?string,customer_incharge_email:?string,biomed_incharge:?string,biomed_email:?string,created_at:string}|null
     */
    public ?array $existingReport = null;

    public string $serviceStatus = 'in_progress';
    public ?string $loginDate = null;
    public ?string $serviceStartAt = null;
    public ?string $serviceEndAt = null;
    public ?string $logoutDate = null;
    public ?string $callLoginTime = null;
    public ?string $machineSystem = null;
    public ?string $serialNumber = null;
    public ?string $softwareVersion = null;
    public ?string $contract = null;
    public ?string $customerIncharge = null;
    public ?string $customerInchargeEmail = null;
    public ?string $biomedIncharge = null;
    public ?string $biomedEmail = null;
    public ?string $problemAndConcerns = null;
    public ?string $jobDone = null;
    public ?string $partsReplaced = null;
    public ?string $recommendation = null;
    public ?string $remarks = null;

    public function mount(
        int $ticketId,
        string $currentUserName,
        string $currentUserRole,
        ?string $currentUserEmail = null,
        ?array $existingReport = null,
    ): void {
        $this->ticketId            = $ticketId;
        $this->currentUserName     = $currentUserName;
        $this->currentUserRole     = $currentUserRole;
        $this->currentUserEmail    = $currentUserEmail;
        $this->existingReport      = $existingReport;
    }

    public function submit(): void
    {
        $payload = [
            'service_status'           => $this->serviceStatus,
            'login_date'               => $this->normalizeDate($this->loginDate),
            'service_start_at'         => $this->normalizeDateTime($this->serviceStartAt),
            'service_end_at'           => $this->normalizeDateTime($this->serviceEndAt),
            'logout_date'              => $this->normalizeDate($this->logoutDate),
            'call_login_time'          => $this->callLoginTime ?: null,
            'machine_system'           => $this->machineSystem ?: null,
            'serial_number'            => $this->serialNumber ?: null,
            'software_version'         => $this->softwareVersion ?: null,
            'contract'                 => $this->contract ?: null,
            'customer_incharge'        => $this->customerIncharge ?: null,
            'customer_incharge_email'  => $this->customerInchargeEmail ?: null,
            'biomed_incharge'          => $this->biomedIncharge ?: null,
            'biomed_email'             => $this->biomedEmail ?: null,
            'problem_and_concerns'     => $this->problemAndConcerns ?: null,
            'job_done'                 => $this->jobDone ?: null,
            'parts_replaced'           => $this->partsReplaced ?: null,
            'recommendation'           => $this->recommendation ?: null,
            'remarks'                  => $this->remarks ?: null,
        ];

        $request = request();
        $request->merge($payload);

        $controller = app(\App\Http\Controllers\Tsp\ServiceReportController::class);
        $payload = $controller->store($request, (string) $this->ticketId)->getData(true);

        // Tell Alpine to flip into the "submitted" state — Pusher will
        // also fire `service-report.submitted` for any other tab.
        $this->dispatch('service-report-submitted', [
            'report_id'        => $payload['id'] ?? null,
            'service_status'   => $payload['service_status'] ?? $this->serviceStatus,
            'new_ticket_status'=> $payload['new_ticket_status'] ?? null,
            'total_minutes'    => $payload['total_minutes'] ?? null,
            'redirect'         => $payload['redirect'] ?? null,
        ]);
    }

    protected function normalizeDate(?string $v): ?string
    {
        if (! $v) return null;
        $ts = strtotime($v);
        return $ts ? date('Y-m-d', $ts) : null;
    }

    protected function normalizeDateTime(?string $v): ?string
    {
        if (! $v) return null;
        $ts = strtotime($v);
        return $ts ? date('Y-m-d H:i:s', $ts) : null;
    }
};
?>

<div
    x-data="serviceReportForm({
        ticketId: @js($ticketId),
        currentUserEmail: @js($currentUserEmail),
        existingReport:  @js($existingReport),
    })"
    x-init="init()"
    @service-report-submitted.window="onSubmitted($event.detail)"
    class="bg-white shadow sm:rounded-lg p-6"
>
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-base font-semibold text-gray-900">Service report</h3>
        <div class="text-xs text-gray-500 flex items-center gap-2">
            <template x-if="existingReport">
                <span class="inline-flex items-center gap-1 text-emerald-600">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    <span>Submitted</span>
                </span>
            </template>
            <template x-if="! existingReport">
                <span class="text-gray-400">Not yet submitted</span>
            </template>
        </div>
    </div>

    {{-- Read-only summary if a report already exists. --}}
    <template x-if="existingReport">
        <div class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="border border-gray-200 rounded p-3">
                    <div class="text-xs uppercase text-gray-500">Service status</div>
                    <div class="mt-1 text-sm font-medium text-gray-900" x-text="existingReport.service_status_label"></div>
                </div>
                <div class="border border-gray-200 rounded p-3">
                    <div class="text-xs uppercase text-gray-500">Submitted by</div>
                    <div class="mt-1 text-sm font-medium text-gray-900" x-text="existingReport.author_name + ' (' + existingReport.author_role + ')'"></div>
                </div>
                <div class="border border-gray-200 rounded p-3">
                    <div class="text-xs uppercase text-gray-500">Time logged on this ticket</div>
                    <div class="mt-1 text-sm font-medium text-gray-900" x-text="existingReport.total_minutes ? Math.floor(existingReport.total_minutes / 60) + 'h ' + (existingReport.total_minutes % 60) + 'm' : '—'"></div>
                </div>
                <div class="border border-gray-200 rounded p-3">
                    <div class="text-xs uppercase text-gray-500">Submitted at</div>
                    <div class="mt-1 text-sm font-medium text-gray-900" x-text="formatTime(existingReport.created_at)"></div>
                </div>
            </div>

            <template x-if="existingReport.problem_and_concerns">
                <div class="border-l-4 border-amber-400 bg-amber-50 px-4 py-3">
                    <div class="text-xs font-semibold text-amber-800 uppercase">Problem &amp; concerns</div>
                    <p class="mt-1 text-sm text-gray-800 whitespace-pre-wrap" x-text="existingReport.problem_and_concerns"></p>
                </div>
            </template>
            <template x-if="existingReport.job_done">
                <div class="border-l-4 border-emerald-400 bg-emerald-50 px-4 py-3">
                    <div class="text-xs font-semibold text-emerald-800 uppercase">Job done</div>
                    <p class="mt-1 text-sm text-gray-800 whitespace-pre-wrap" x-text="existingReport.job_done"></p>
                </div>
            </template>
            <template x-if="existingReport.parts_replaced">
                <div class="border-l-4 border-sky-400 bg-sky-50 px-4 py-3">
                    <div class="text-xs font-semibold text-sky-800 uppercase">Parts replaced</div>
                    <p class="mt-1 text-sm text-gray-800 whitespace-pre-wrap" x-text="existingReport.parts_replaced"></p>
                </div>
            </template>
            <template x-if="existingReport.recommendation">
                <div class="border-l-4 border-indigo-400 bg-indigo-50 px-4 py-3">
                    <div class="text-xs font-semibold text-indigo-800 uppercase">Recommendation</div>
                    <p class="mt-1 text-sm text-gray-800 whitespace-pre-wrap" x-text="existingReport.recommendation"></p>
                </div>
            </template>
            <template x-if="existingReport.remarks">
                <div class="border-l-4 border-gray-400 bg-gray-50 px-4 py-3">
                    <div class="text-xs font-semibold text-gray-800 uppercase">Remarks</div>
                    <p class="mt-1 text-sm text-gray-800 whitespace-pre-wrap" x-text="existingReport.remarks"></p>
                </div>
            </template>
        </div>
    </template>

    {{-- Editable form. --}}
    <template x-if="! existingReport">
        <form wire:submit.prevent="submit" class="space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700">Service status</label>
                    <select
                        wire:model="serviceStatus"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                    >
                        <option value="open">Open</option>
                        <option value="in_progress">In progress</option>
                        <option value="pending">Pending</option>
                        <option value="escalated">Escalated</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700">Login date</label>
                    <input type="date" wire:model="loginDate"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700">Logout date</label>
                    <input type="date" wire:model="logoutDate"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700">Service start</label>
                    <input type="datetime-local" wire:model="serviceStartAt"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700">Service end</label>
                    <input type="datetime-local" wire:model="serviceEndAt"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700">Call login time</label>
                    <input type="time" wire:model="callLoginTime"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700">Machine / system</label>
                    <input type="text" wire:model="machineSystem" maxlength="100"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700">Serial number</label>
                    <input type="text" wire:model="serialNumber" maxlength="200"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700">Software version</label>
                    <input type="text" wire:model="softwareVersion" maxlength="200"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700">Contract</label>
                    <select wire:model="contract"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <option value="">—</option>
                        <option value="Purchased">Purchased</option>
                        <option value="RTU">RTU</option>
                        <option value="Demo">Demo</option>
                        <option value="Backup">Backup</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700">Customer in-charge</label>
                    <input type="text" wire:model="customerIncharge" maxlength="200"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700">Customer in-charge email</label>
                    <input type="email" wire:model="customerInchargeEmail" maxlength="200"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700">Biomed in-charge</label>
                    <input type="text" wire:model="biomedIncharge" maxlength="200"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700">Biomed email</label>
                    <input type="email" wire:model="biomedEmail" maxlength="200"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700">Problem &amp; concerns</label>
                <textarea wire:model="problemAndConcerns" rows="3" maxlength="10000"
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"></textarea>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700">Job done</label>
                <textarea wire:model="jobDone" rows="3" maxlength="10000"
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"></textarea>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700">Parts replaced</label>
                <textarea wire:model="partsReplaced" rows="2" maxlength="10000"
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"></textarea>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700">Recommendation</label>
                <textarea wire:model="recommendation" rows="2" maxlength="10000"
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"></textarea>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700">Remarks</label>
                <textarea wire:model="remarks" rows="2" maxlength="10000"
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3">
                <span
                    x-show="submitting" x-cloak
                    class="text-xs text-indigo-600"
                >Submitting…</span>
                <button
                    type="submit"
                    :disabled="submitting"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-800 focus:outline-none focus:border-indigo-800 focus:ring focus:ring-indigo-200 transition disabled:opacity-50"
                >
                    Submit service report
                </button>
            </div>
        </form>
    </template>
</div>
