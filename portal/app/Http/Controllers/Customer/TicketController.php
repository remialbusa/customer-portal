<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\MondayClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return view('customer.tickets.create', [
            'user'        => $user,
            'machines'    => $machines,
            'requestTypes'=> ['Issue', 'Request'],
        ]);
    }

    /**
     * Validate and push the new ticket to Monday.
     */
    public function store(Request $request, MondayClient $monday): RedirectResponse
    {
        $data = $request->validate([
            'subject'      => ['required', 'string', 'max:255'],
            'description'  => ['required', 'string', 'max:5000'],
            'request_type' => ['required', Rule::in(['Issue', 'Request'])],
            'machine_id'   => ['nullable', 'exists:machines,id'],
            'brand'        => ['nullable', 'string', 'max:120'],
            'model'        => ['nullable', 'string', 'max:120'],
            'serial'       => ['nullable', 'string', 'max:120'],
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

        $result = $monday->createTicket([
            'name'            => $data['subject'],
            'description'     => $data['description'],
            'request_type'    => $data['request_type'],
            'customer_email'  => $user->email,
            'customer_item_id'=> $customerItemId,
            'brand'           => $brand,
            'model'           => $model,
            'serial'          => $serial,
        ]);

        if (empty($result['id'])) {
            return back()
                ->withInput()
                ->withErrors(['monday' => 'Monday.com did not return a ticket id. Please try again or contact support.']);
        }

        return redirect()
            ->route('dashboard')
            ->with('status', "Ticket #{$result['id']} submitted — our team has been notified.");
    }
}
