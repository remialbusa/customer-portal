<?php

namespace App\Http\Controllers;

use App\Models\AccountDeletionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * File a request to delete the authenticated user's account.
 *
 * Customers and TSPs cannot delete their own accounts directly —
 * Breeze's default self-delete form is disabled for them and
 * replaced with this endpoint, which creates a row in
 * account_deletion_requests. The superadmin reviews the request
 * in /admin/deletion-requests and either approves (which actually
 * deletes the user) or rejects it.
 *
 * Superadmins keep the original self-delete flow because they are
 * trusted to manage their own account.
 */
class ProfileDeletionRequestController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            // Defensive: if a superadmin hits this endpoint, fall
            // through to the self-delete path so the request
            // still goes through Breeze's component.
            return redirect()->route('profile.edit');
        }

        // Block duplicate pending requests. The user can cancel
        // the existing one first if they want to re-submit with
        // a different reason.
        $existing = AccountDeletionRequest::latestPendingFor($user->id);
        if ($existing) {
            return back()->withErrors([
                'request' => 'You already have a pending account-deletion request. Please wait for the superadmin to review it.',
            ]);
        }

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        AccountDeletionRequest::create([
            'user_id' => $user->id,
            'email'   => $user->email,
            'name'    => $user->name,
            'role'    => $user->role,
            'reason'  => $data['reason'] ?? null,
            'status'  => AccountDeletionRequest::STATUS_PENDING,
        ]);

        return back()->with('status', 'Your account-deletion request has been submitted to the superadmin for review.');
    }

    /**
     * Cancel the requester's own pending request. We allow this
     * so a user who filed by mistake can take it back without
     * having to email support.
     */
    public function cancel(Request $request): RedirectResponse
    {
        $user    = $request->user();
        $request = AccountDeletionRequest::latestPendingFor($user->id);

        if (! $request) {
            return back()->withErrors([
                'request' => 'No pending request to cancel.',
            ]);
        }

        $request->forceFill([
            'status'        => AccountDeletionRequest::STATUS_CANCELLED,
            'processed_at'  => now(),
            'decision_note' => 'Cancelled by requester',
        ])->save();

        return back()->with('status', 'Your account-deletion request has been cancelled.');
    }
}
