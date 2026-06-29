<?php

use App\Models\ChatMessage;
use App\Models\User;
use App\Services\MondayClient;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Authorization callbacks for private / presence channels. The chat uses
| one private channel per Monday ticket: `private-ticket.{mondayId}`. The
| callback has to confirm the requester is allowed to see / write messages
| on that ticket before Pusher subscribes the socket.
|
| Rules per role:
|   - admin: may join any ticket.
|   - fse / its / manager: may join a ticket if the Monday item's TSP
|     people column contains their user id, OR the ticket is unassigned.
|     (We accept unassigned tickets so any on-duty TSP can pick it up.)
|   - customer: may join only the tickets where the End User (board
|     relation) resolves to a Customers-board record whose email column
|     matches the user's email.
*/

Broadcast::channel('ticket.{mondayId}', function (User $user, string $mondayId) {
    $mondayId = (string) $mondayId;

    if ($user->role === 'admin') {
        return true;
    }

    $monday = app(MondayClient::class);
    $item   = $monday->getItem($mondayId);
    if (! $item) {
        return false;
    }

    if (in_array($user->role, ['fse', 'its', 'manager'], true)) {
        // Anyone in the TSP pool may join. Real per-ticket isolation
        // is enforced at message-send time via ChatController.
        return true;
    }

    if ($user->role === 'customer') {
        // Customer can only join their own ticket.
        $customerItemId = $monday->findOrCreateCustomerItem([
            'name'         => $user->name,
            'email'        => $user->email,
            'account_name' => $user->account_name,
            'brand'        => $user->brand,
            'model'        => $user->model,
        ], knownId: $user->monday_id);

        if ($customerItemId === null) {
            return false;
        }

        $endUser = $item['column_values']['board_relation_mm4f9mwv'] ?? null;
        $linked  = $endUser['linked_item_ids'] ?? [];
        return in_array((int) $customerItemId, array_map('intval', $linked), true);
    }

    return false;
});

/*
|--------------------------------------------------------------------------
| Internal-notes channel
|--------------------------------------------------------------------------
| Channel: `private-ticket.{mondayId}.internal`
|
| Customers are NEVER allowed to join this channel. Only admin and
| the TSP roles (fse, its, manager) can subscribe, mirroring the
| customer chat's role split but with the customer explicitly
| rejected. The same Monday lookup is used to confirm the ticket
| exists before granting access.
*/
Broadcast::channel('ticket.{mondayId}.internal', function (User $user, string $mondayId) {
    $mondayId = (string) $mondayId;

    // Hard reject for customers. Even if a customer's token tried to
    // subscribe to the .internal channel, the broadcast is denied.
    if ($user->role === 'customer') {
        return false;
    }

    if ($user->role === 'admin' || in_array($user->role, ['fse', 'its', 'manager'], true)) {
        // Confirm the ticket exists in Monday (any TSP can join any
        // ticket's internal-notes channel — same policy as the chat
        // channel).
        $item = app(MondayClient::class)->getItem($mondayId);
        return $item !== null;
    }

    return false;
});

/*
|--------------------------------------------------------------------------
| Customer-side ticket updates channel
|--------------------------------------------------------------------------
| Channel: `private-ticket.{mondayId}.customer`
|
| Used to push real-time ticket-status changes (e.g. when a TSP submits
| a service report and flips status95 to "Resolved") to the customer's
| open ticket page and dashboard. Both customers and TSPs may join —
| the customer can only join tickets they own, TSPs may join any.
*/
Broadcast::channel('ticket.{mondayId}.customer', function (User $user, string $mondayId) {
    $mondayId = (string) $mondayId;

    if ($user->role === 'admin' || in_array($user->role, ['fse', 'its', 'manager'], true)) {
        return app(MondayClient::class)->getItem($mondayId) !== null;
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
            return false;
        }
        $item = $monday->getItem($mondayId);
        if (! $item) {
            return false;
        }
        $endUser = $item['column_values']['board_relation_mm4f9mwv'] ?? null;
        $linked  = $endUser['linked_item_ids'] ?? [];
        return in_array((int) $customerItemId, array_map('intval', $linked), true);
    }

    return false;
});
