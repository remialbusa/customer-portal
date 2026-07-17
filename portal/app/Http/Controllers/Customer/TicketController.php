<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\MondayClient;
use App\Support\PersonnelDirectory;
use App\Support\RegionResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TicketController extends Controller
{
    /**
     * Show the new-ticket form.
     */
    public function create(): View
    {
        $user = auth()->user();

        // Load customer's registered machines
        $machines = \App\Models\Machine::where('user_id', $user->id)
            ->orderBy('is_primary', 'desc')
            ->orderBy('brand')
            ->get();

        // Resolve the customer's physical region so the TSP picker
        // can be scoped to the team physically closest to them. Falls
        // back to null when we can't tell where the customer is (e.g.
        // a brand-new self-registered account that never filled in a
        // branch or address) — in that case the picker shows all 4
        // region groups so the customer can still pick someone.
        $customerRegion = RegionResolver::resolveForCustomer($user);

        return view('customer.tickets.create', [
            'user'            => $user,
            'machines'        => $machines,
            'requestTypes'    => ['Issue', 'Request'],
            'tspDirectory'    => PersonnelDirectory::forCustomerAssignment($customerRegion),
            'customerRegion'  => $customerRegion,
        ]);
    }

    /**
     * Validate and push the new ticket to Monday.
     */
    public function store(Request $request, MondayClient $monday): RedirectResponse
    {
        // The TSP picker posts user[] ids (the local `users` table ids).
        // We accept any number of integers; downstream resolveMondayPersonIds()
        // will reject any that don't have a monday_id populated.
        $data = $request->validate([
            'subject'              => ['required', 'string', 'max:255'],
            'description'          => ['required', 'string', 'max:5000'],
            'request_type'         => ['required', Rule::in(['Issue', 'Request'])],
            'machine_id'           => ['nullable', 'exists:machines,id'],
            'brand'                => ['nullable', 'string', 'max:120'],
            'model'                => ['nullable', 'string', 'max:120'],
            'serial'               => ['nullable', 'string', 'max:120'],
            'assigned_tsp_ids'     => ['nullable', 'array', 'max:10'],
            'assigned_tsp_ids.*'   => ['integer', 'exists:users,id'],
        ]);

        $user = $request->user();

        // If machine_id is provided, load the machine and use its brand/model
        $brand = $data['brand'] ?? null;
        $model = $data['model'] ?? null;
        $serial = $data['serial'] ?? null;
        
        if (!empty($data['machine_id'])) {
            $machine = \App\Models\Machine::find($data['machine_id']);
            if ($machine && $machine->user_id === $user->id) {
                $brand = $machine->brand;
                $model = $machine->model;
                $serial = $machine->serial_number ?? $serial;
            }
        }

        // Soft duplicate guard: if this customer already has an OPEN
        // ticket with the same subject, refuse the submit on the first
        // try and let the form show the existing ticket(s) so the user
        // can decide. The "Submit anyway" button on the form posts to
        // the same endpoint with ?force=1, which bypasses the check.
        //
        // We do this AFTER validation so the form's @errors block still
        // shows the validation messages above the duplicate warning,
        // but BEFORE findOrCreateCustomerItem() so we don't create a
        // new Monday customer record for a request we're about to
        // reject.
        if (! $request->boolean('force')) {
            $duplicates = $monday->findOpenDuplicateTicketForCustomer(
                $user->email,
                $data['subject']
            );
            if (! empty($duplicates)) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'duplicate' => 'You already have an open ticket with the same subject. Review it below before submitting again.',
                    ])
                    ->with('duplicate_tickets', $duplicates);
            }
        }

        // Even with ?force=1, refuse the submit if the customer
        // already has 2+ open tickets with the same subject. The
        // first force is allowed (legit "I really need a new
        // ticket" cases) but anything beyond that is almost
        // certainly a duplicate-spam scenario and must be
        // resolved by the support team. Without this guard a
        // user could bypass the UI cap by crafting a raw POST.
        if ($request->boolean('force')) {
            $duplicates = $monday->findOpenDuplicateTicketForCustomer(
                $user->email,
                $data['subject']
            );
            if (count($duplicates) >= 2) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'duplicate' => "You already have " . count($duplicates) . " open tickets with this subject. Please contact our support team to consolidate them before submitting a new one.",
                    ])
                    ->with('duplicate_tickets', $duplicates);
            }
        }

        // Link the ticket to a Monday customer record. If the user has no
        // matching record on the Customers board yet, create one on the fly
        // and remember the id on the local user row so we don't repeat
        // the lookup on every form submit.
        //
        // The cached id can become stale (Monday item deleted in the UI),
        // so we always pass it through findOrCreateCustomerItem which
        // transparently re-resolves by email if the cached id is dead.
        $customerItemId = $monday->findOrCreateCustomerItem([
            'name'         => $user->name,
            'email'        => $user->email,
            'account_name' => $user->account_name,
            'brand'        => $brand,
            'model'        => $model,
        ], knownId: $user->monday_id);

        if ($customerItemId !== null && $customerItemId !== $user->monday_id) {
            // Either first-time resolution or stale cache replaced — persist.
            $user->forceFill(['monday_id' => $customerItemId])->save();
        }

        $tspPersonIds = $this->resolveTspPersonIds($data['assigned_tsp_ids'] ?? []);

        $result = $monday->createTicket([
            'name'            => $data['subject'],
            'description'     => $data['description'],
            'request_type'    => $data['request_type'],
            'customer_email'  => $user->email,
            'customer_item_id'=> $customerItemId,
            'brand'           => $brand,
            'model'           => $model,
            'serial'          => $serial,
            'tsp_person_ids'  => $tspPersonIds,
        ]);

        if (empty($result['id'])) {
            return back()
                ->withInput()
                ->withErrors(['monday' => 'Monday.com did not return a ticket id. Please try again or contact support.']);
        }

        // Flip RESPONSE STATUS to "RESPONDED" once the ticket is created
        // AND at least one TSP is actually assigned (the People column
        // ends up non-empty). The "no preference" path leaves the column
        // at "NOT YET" so the status accurately reflects whether a
        // specific TSP was picked.
        //
        // Best-effort: a failure here must NOT block the redirect. The
        // customer has successfully submitted the ticket — losing the
        // status update is recoverable, but losing the redirect is a
        // confusing 500. markTicketResponded() also internally guards
        // against a missing config entry.
        if (! empty($tspPersonIds)) {
            try {
                $monday->markTicketResponded((int) $result['id']);
            } catch (\Throwable $e) {
                Log::warning('TicketController: markTicketResponded failed', [
                    'ticket_id' => $result['id'],
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        return redirect()
            ->route('dashboard')
            ->with('status', "Ticket #{$result['id']} submitted — our team has been notified.");
    }

    /**
     * Translate the local user ids the customer checked on the form into
     * the Monday person ids we need to populate the TSP People column.
     *
     * Returns an empty array if the customer didn't pick anyone (the
     * "No preference" / blank form is a valid submission). Throws
     * InvalidArgumentException if any of the selected ids doesn't have
     * a monday_id — that condition is treated as a validation error
     * because the picker should have disabled those checkboxes, so
     * seeing one in the payload means a stale form was submitted.
     */
    private function resolveTspPersonIds(array $userIds): array
    {
        $userIds = array_values(array_filter(
            array_map('intval', $userIds),
            static fn (int $id) => $id > 0
        ));
        if (empty($userIds)) {
            return [];
        }

        return PersonnelDirectory::resolveMondayPersonIds($userIds);
    }
}
