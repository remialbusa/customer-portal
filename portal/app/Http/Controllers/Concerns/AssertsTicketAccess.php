<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use App\Services\MondayClient;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

/**
 * Shared authorization helpers for the ticket-scoped controllers
 * (customer ticket show + chat, TSP ticket show + chat).
 *
 * The Monday.com API is the source of truth for who can see what
 * ticket, so we lean on `MondayClient::getItem()` and a few
 * cheap lookups to resolve the current user's customer record.
 */
trait AssertsTicketAccess
{
    /**
     * Resolve the Monday ticket, abort with 404 if it doesn't exist.
     * Returns the normalized item shape.
     */
    protected function loadMondayTicket(string $mondayId): array
    {
        $item = app(MondayClient::class)->getItem($mondayId);
        if (! $item) {
            abort(404, "Ticket #{$mondayId} not found in Monday.");
        }
        return $item;
    }

    /**
     * Authorize the requester against the ticket.
     *
     *  - admin: always allowed.
     *  - fse / its / manager: allowed (per-ticket assignment isolation
     *    is not enforced yet — that's a Phase 4 hardening step).
     *  - customer: allowed only if the End User relation on the ticket
     *    points to a Customers-board item that resolves to the user.
     *
     * On failure, throws an HttpResponseException with 403.
     */
    protected function authorizeTicketAccess(User $user, array $item): void
    {
        if ($user->role === 'admin' || in_array($user->role, ['fse', 'its', 'manager'], true)) {
            return;
        }

        if ($user->role === 'customer') {
            $monday = app(MondayClient::class);
            $customerItemId = $monday->findOrCreateCustomerItem([
                'name'         => $user->name,
                'email'        => $user->email,
                'account_name' => $user->account_name,
                'brand'        => $user->brand,
                'model'        => $user->model,
            ], knownId: $user->monday_id);

            if ($customerItemId === null) {
                throw new HttpResponseException(response()->view('errors.403', [
                    'message' => 'We could not locate your customer record on Monday.com.',
                ], Response::HTTP_FORBIDDEN));
            }

            $endUser = $item['column_values']['board_relation_mm4f9mwv'] ?? null;
            $linked  = $endUser['linked_item_ids'] ?? [];
            if (! in_array((int) $customerItemId, array_map('intval', $linked), true)) {
                throw new HttpResponseException(response()->view('errors.403', [
                    'message' => 'You do not have access to this ticket.',
                ], Response::HTTP_FORBIDDEN));
            }

            // Persist the freshly-resolved id (covers first-time login).
            if ($customerItemId !== $user->monday_id) {
                $user->forceFill(['monday_id' => $customerItemId])->save();
            }
            return;
        }

        throw new HttpResponseException(response()->view('errors.403', [
            'message' => 'Your role does not have access to this ticket.',
        ], Response::HTTP_FORBIDDEN));
    }
}
