<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountDeletionRequest;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Admin/superadmin user management.
 *
 * Deliberately a plain controller + Blade page, matching the other
 * admin screens (KpiController, AccountDeletionRequestController)
 * rather than adding a Livewire component.
 *
 * WHAT THIS PAGE CANNOT DO
 * ------------------------
 * Two guard rails apply to every action here:
 *
 *  1. You cannot act on your own account. An admin who deletes
 *     themselves is an admin who cannot undo it.
 *  2. You cannot act on an admin or superadmin at all — not suspend,
 *     not delete, not even edit. Editing is included on purpose:
 *     "edit" carries the role field, so leaving it open would let a
 *     regular admin demote a superadmin and take over the account.
 *     Privileged accounts are managed in the database, not from here.
 *
 * Role changes are additionally restricted to the operational roles,
 * so the page cannot be used to mint another admin.
 */
class UserController extends Controller
{
    /**
     * Roles this page may assign. Privileged roles are excluded so the
     * UI can never be used to create or promote an admin.
     */
    public const ASSIGNABLE_ROLES = ['customer', 'fse', 'its', 'manager'];

    /**
     * Roles that can be used as a filter, including the privileged
     * ones — useful for finding them, even though they cannot be
     * modified here.
     */
    public const FILTERABLE_ROLES = ['customer', 'fse', 'its', 'manager', 'admin', 'superadmin'];

    public const STATUSES = ['active', 'suspended'];

    public function index(Request $request): View
    {
        $filters = [
            'q'      => trim((string) $request->query('q', '')),
            'role'   => (string) $request->query('role', ''),
            'status' => (string) $request->query('status', ''),
        ];

        $users = User::query()
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $like = '%' . $filters['q'] . '%';
                $query->where(function ($w) use ($like) {
                    $w->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('account_name', 'like', $like);
                });
            })
            ->when(
                in_array($filters['role'], self::FILTERABLE_ROLES, true),
                fn ($query) => $query->where('role', $filters['role'])
            )
            ->when(
                in_array($filters['status'], self::STATUSES, true),
                fn ($query) => $query->where('status', $filters['status'])
            )
            // Privileged accounts first, then by name, so the list reads
            // predictably instead of by insertion order.
            ->orderByRaw(
                "case role when 'superadmin' then 1 when 'admin' then 2"
                . " when 'manager' then 3 when 'its' then 4 when 'fse' then 5 else 6 end"
            )
            ->orderBy('name')
            ->get();

        return view('admin.users.index', [
            'authUser' => $request->user(),
            'users'    => $users,
            'filters'  => $filters,
            'roles'    => self::FILTERABLE_ROLES,
            'statuses' => self::STATUSES,
            'counts'   => [
                'total'     => User::count(),
                'active'    => User::where('status', 'active')->count(),
                'suspended' => User::where('status', 'suspended')->count(),
            ],
        ]);
    }

    public function edit(Request $request, User $user): View
    {
        $this->assertManageable($request->user(), $user);

        return view('admin.users.edit', [
            'authUser' => $request->user(),
            'target'   => $user,
            'roles'    => self::ASSIGNABLE_ROLES,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        $this->assertManageable($actor, $user);

        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'email'        => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'role'         => ['required', Rule::in(self::ASSIGNABLE_ROLES)],
            'region'       => ['nullable', 'string', 'max:100'],
            'account_name' => ['nullable', 'string', 'max:255'],
            'branch'       => ['nullable', 'string', 'max:255'],
            'address'      => ['nullable', 'string', 'max:500'],
            'team'         => ['nullable', 'string', 'max:100'],
        ]);

        $user->fill($data)->save();

        Log::info('admin.users.updated', [
            'actor_id' => $actor->id,
            'user_id'  => $user->id,
            'email'    => $user->email,
            'role'     => $user->role,
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('status', "Saved changes to {$user->email}.");
    }

    /**
     * Suspend: block sign-in but keep everything. Reversible, and the
     * reason this page exists — a suspension is the safe answer when
     * someone leaves or an account is in question, where a delete
     * would destroy their service reports and chat history.
     */
    public function suspend(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        $this->assertManageable($actor, $user);

        if ($user->status === 'suspended') {
            return back()->with('status', "{$user->email} is already suspended.");
        }

        $user->forceFill(['status' => 'suspended'])->save();

        Log::info('admin.users.suspended', [
            'actor_id' => $actor->id,
            'user_id'  => $user->id,
            'email'    => $user->email,
        ]);

        return back()->with('status', "{$user->email} has been suspended — they can no longer sign in, and nothing was deleted.");
    }

    public function reactivate(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        $this->assertManageable($actor, $user);

        if ($user->status === 'active') {
            return back()->with('status', "{$user->email} is already active.");
        }

        $user->forceFill(['status' => 'active'])->save();

        Log::info('admin.users.reactivated', [
            'actor_id' => $actor->id,
            'user_id'  => $user->id,
            'email'    => $user->email,
        ]);

        return back()->with('status', "{$user->email} has been reactivated.");
    }

    /**
     * Permanent delete. This cascades: the account's service reports,
     * time entries, chat messages, internal notes and machines go with
     * it (see the foreign keys in the migrations). Guarded by a typed
     * confirmation — the server compares the typed email to the
     * account, so a stray browser click cannot trigger it.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        $this->assertManageable($actor, $user);

        $data = $request->validate([
            'confirm_email' => ['required', 'string'],
            'reason'        => ['nullable', 'string', 'max:500'],
        ]);

        if (strtolower(trim($data['confirm_email'])) !== strtolower(trim((string) $user->email))) {
            throw ValidationException::withMessages([
                'confirm_email' => 'The email you typed does not match this account, so nothing was deleted.',
            ]);
        }

        // Snapshot everything the audit row needs before the row goes.
        $userId = $user->id;
        $email  = $user->email;
        $name   = $user->name;
        $role   = $user->role;

        DB::transaction(function () use ($user, $actor, $userId, $email, $name, $role, $data) {
            // Audit first. account_deletion_requests.user_id is
            // nullable with nullOnDelete, so this survives the delete
            // via its own email/name/role snapshot.
            AccountDeletionRequest::create([
                'user_id'       => $userId,
                'email'         => $email,
                'name'          => $name,
                'role'          => $role,
                'reason'        => $data['reason'] ?? 'Deleted manually by an administrator.',
                'status'        => AccountDeletionRequest::STATUS_APPROVED,
                'processed_by'  => $actor->id,
                'processed_at'  => now(),
                'decision_note' => 'Manual delete by ' . $actor->name,
            ]);

            // notifications.user_id has no foreign key, so nothing else
            // clears these — they would be orphaned rows pointing at a
            // user who no longer exists.
            Notification::where('user_id', $userId)->delete();

            $user->delete();
        });

        Log::info('admin.users.deleted', [
            'actor_id' => $actor->id,
            'user_id'  => $userId,
            'email'    => $email,
            'role'     => $role,
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('status', "Account for {$email} has been permanently deleted.");
    }

    /**
     * The two guard rails, in one place so every action is covered by
     * construction rather than by remembering to check.
     *
     * @throws ValidationException
     */
    protected function assertManageable(User $actor, User $target): void
    {
        if ($actor->is($target)) {
            throw ValidationException::withMessages([
                'user' => 'You cannot manage your own account from this page.',
            ]);
        }

        if ($target->isAdmin() || $target->isSuperAdmin()) {
            throw ValidationException::withMessages([
                'user' => 'Admin and superadmin accounts cannot be managed from this page.',
            ]);
        }
    }
}
