@php
    // Role labels + badge colours. Privileged roles are shown but are
    // never actionable (the controller refuses, and the buttons below
    // are not rendered for them).
    $roleLabels = [
        'customer'   => 'Customer',
        'fse'        => 'Field engineer',
        'its'        => 'IT specialist',
        'manager'    => 'Manager',
        'admin'      => 'Admin',
        'superadmin' => 'Superadmin',
    ];
    $roleBadges = [
        'customer'   => 'badge-ghost',
        'fse'        => 'badge-info',
        'its'        => 'badge-info',
        'manager'    => 'badge-primary',
        'admin'      => 'badge-warning',
        'superadmin' => 'badge-error',
    ];
    $filterActive = $filters['q'] !== '' || $filters['role'] !== '' || $filters['status'] !== '';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <p class="text-xs font-semibold tracking-widest uppercase text-base-content/50 mb-1">
                    Administration
                </p>
                <h2 class="font-semibold text-2xl text-base-content leading-tight">
                    Users &amp; accounts
                </h2>
                <p class="text-sm text-base-content/60 mt-1">
                    Suspend, edit, or permanently delete an account.
                </p>
            </div>
            <div class="flex items-center gap-2 self-start sm:self-auto">
                <a href="{{ route('admin.kpi') }}" wire:navigate class="btn btn-ghost btn-sm gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    KPI dashboard
                </a>
                @if ($authUser->isSuperAdmin())
                    <a href="{{ route('admin.deletion-requests') }}" wire:navigate class="btn btn-ghost btn-sm">
                        Delete requests
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    {{-- x-data lives on the outer wrapper so the delete modal (a
         sibling of the list, see the bottom of this view) shares the
         same scope. Putting it on the inner container leaves the modal
         unable to see `del`. --}}
    <div class="py-2"
         x-data="{
            del: { id: '', name: '', email: '', confirm: '', reason: '' },
            canConfirm() {
                return this.del.confirm.trim().toLowerCase() !== ''
                    && this.del.confirm.trim().toLowerCase() === (this.del.email || '').toLowerCase();
            }
         }">
        <div class="max-w-6xl mx-auto sm:px-4 lg:px-6 space-y-6">

            @if (session('status'))
                <x-ui.toast type="success" title="All set!">
                    {{ session('status') }}
                </x-ui.toast>
            @endif

            @if ($errors->any())
                <div role="alert" class="alert alert-error shadow-sm flex-col items-start gap-1 p-4">
                    <h3 class="font-semibold text-sm">That action was not applied</h3>
                    <ul class="list-disc list-inside text-xs space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- ── Totals ─────────────────────────────────────────── --}}
            <div class="grid grid-cols-3 gap-3">
                @foreach ([
                    ['label' => 'Total accounts', 'value' => $counts['total'],     'tone' => 'text-base-content'],
                    ['label' => 'Active',         'value' => $counts['active'],    'tone' => 'text-success'],
                    ['label' => 'Suspended',      'value' => $counts['suspended'], 'tone' => 'text-warning'],
                ] as $stat)
                    <div class="card bg-base-100 border border-base-300/70 shadow-soft rounded-2xl px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-base-content/50">{{ $stat['label'] }}</p>
                        <p class="text-2xl font-semibold {{ $stat['tone'] }} leading-tight mt-0.5">{{ $stat['value'] }}</p>
                    </div>
                @endforeach
            </div>

            {{-- ── Filters ────────────────────────────────────────── --}}
            <form method="GET" action="{{ route('admin.users.index') }}"
                  class="card bg-base-100 border border-base-300/70 shadow-soft rounded-2xl p-4">
                <div class="flex flex-col sm:flex-row sm:items-end gap-3">
                    <div class="flex-1 min-w-0">
                        <label for="user-search" class="block text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-1.5">
                            Search
                        </label>
                        <input id="user-search" type="search" name="q" value="{{ $filters['q'] }}"
                               placeholder="Name, email, or hospital"
                               class="input input-bordered w-full h-10 rounded-lg text-sm">
                    </div>
                    <div class="sm:w-44">
                        <label for="user-role" class="block text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-1.5">
                            Role
                        </label>
                        <select id="user-role" name="role" class="select select-bordered w-full h-10 rounded-lg text-sm">
                            <option value="">All roles</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role }}" @selected($filters['role'] === $role)>{{ $roleLabels[$role] ?? $role }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:w-40">
                        <label for="user-status" class="block text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-1.5">
                            Status
                        </label>
                        <select id="user-status" name="status" class="select select-bordered w-full h-10 rounded-lg text-sm">
                            <option value="">Any status</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="submit" class="btn btn-primary btn-sm h-10">Filter</button>
                        @if ($filterActive)
                            <a href="{{ route('admin.users.index') }}" wire:navigate class="btn btn-ghost btn-sm h-10">Clear</a>
                        @endif
                    </div>
                </div>
            </form>

            {{-- ── The list ───────────────────────────────────────── --}}
            <x-ui.card
                :title="$filterActive ? 'Matching accounts' : 'All accounts'"
                :subtitle="$users->count() . ' shown' . ($filterActive ? ' (filtered)' : '')"
                padding="p-0"
            >
                <x-slot:icon>
                    <span aria-hidden="true" class="w-7 h-7 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </span>
                </x-slot:icon>

                @if ($users->isEmpty())
                    <div class="px-6 py-12 text-center">
                        <p class="text-sm font-medium text-base-content">No accounts match this filter.</p>
                        @if ($filterActive)
                            <a href="{{ route('admin.users.index') }}" wire:navigate class="link link-primary text-sm mt-1 inline-block">
                                Clear the filter
                            </a>
                        @endif
                    </div>
                @else
                    <ul class="divide-y divide-base-300/70">
                        @foreach ($users as $u)
                            @php
                                $isSelf = $u->is($authUser);
                                $isPrivileged = $u->isAdmin() || $u->isSuperAdmin();
                                // Matches UserController::assertManageable().
                                $manageable = ! $isSelf && ! $isPrivileged;
                                $scope = $u->role === 'customer'
                                    ? ($u->account_name ?: null)
                                    : ($u->region ?: null);
                            @endphp
                            <li class="px-4 sm:px-5 py-4">
                                <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">

                                    {{-- Identity --}}
                                    <div class="flex items-start gap-3 flex-1 min-w-0">
                                        <span aria-hidden="true" class="w-9 h-9 rounded-full bg-primary/10 text-primary inline-flex items-center justify-center text-sm font-semibold shrink-0">
                                            {{ strtoupper(substr($u->name ?? '?', 0, 1)) }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-base-content break-words">
                                                {{ $u->name ?? '(no name)' }}
                                                @if ($isSelf)
                                                    <span class="text-xs font-normal text-base-content/50">(you)</span>
                                                @endif
                                            </p>
                                            <p class="text-xs text-base-content/60 break-all">{{ $u->email }}</p>
                                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-1.5">
                                                <span class="badge {{ $roleBadges[$u->role] ?? 'badge-ghost' }} badge-sm">
                                                    {{ $roleLabels[$u->role] ?? ($u->role ?? 'unknown') }}
                                                </span>
                                                @if ($u->status !== 'active')
                                                    <span class="badge badge-warning badge-sm">{{ ucfirst($u->status) }}</span>
                                                @endif
                                                @if ($scope)
                                                    <span class="text-xs text-base-content/50">{{ $scope }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Actions --}}
                                    <div class="flex flex-wrap items-center gap-2 shrink-0 sm:justify-end">
                                        @if ($manageable)
                                            <a href="{{ route('admin.users.edit', $u) }}" wire:navigate
                                               class="btn btn-ghost btn-sm h-9 gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                Edit
                                            </a>

                                            @if ($u->status === 'suspended')
                                                <form method="POST" action="{{ route('admin.users.reactivate', $u) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline btn-sm h-9 gap-1">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                                        Reactivate
                                                    </button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('admin.users.suspend', $u) }}"
                                                      onsubmit="return confirm('Suspend {{ $u->email }}? They will not be able to sign in. Nothing is deleted, so this can be undone.');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline btn-sm h-9 gap-1">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                                        Suspend
                                                    </button>
                                                </form>
                                            @endif

                                            <button type="button"
                                                    data-user-id="{{ $u->id }}"
                                                    data-user-name="{{ $u->name }}"
                                                    data-user-email="{{ $u->email }}"
                                                    x-on:click="del = { id: $el.dataset.userId, name: $el.dataset.userName, email: $el.dataset.userEmail, confirm: '', reason: '' }; $dispatch('open-modal', 'delete-user')"
                                                    class="btn btn-ghost btn-sm h-9 text-error hover:bg-error/10 gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/></svg>
                                                Delete
                                            </button>
                                        @else
                                            <span class="text-xs text-base-content/45 italic px-1">
                                                {{ $isSelf ? 'Your account' : 'Protected account' }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>
        </div>

        {{-- ── Delete confirmation ─────────────────────────────────
             One modal for the whole list, filled from the row's
             data-* attributes. The server re-checks that the typed
             email matches, so this is a guard rail, not the guard. --}}
        <x-modal name="delete-user" max-width="lg" focusable>
            <form method="POST" :action="'{{ url('admin/users') }}/' + del.id" class="p-6">
                @csrf
                @method('DELETE')

                <h3 class="text-base font-semibold text-base-content">
                    Permanently delete this account?
                </h3>
                <p class="mt-2 text-sm text-base-content/70">
                    This removes
                    <span class="font-semibold text-base-content break-all" x-text="del.email"></span>
                    and cannot be undone.
                </p>

                <div class="mt-3 rounded-lg bg-error/10 border border-error/20 px-3 py-2.5">
                    <p class="text-xs text-base-content/80">
                        <span class="font-semibold">This also destroys their history:</span>
                        service reports and time entries, chat messages and internal notes,
                        and their registered machines. If you only need to block sign-in,
                        use <span class="font-semibold">Suspend</span> instead.
                    </p>
                </div>

                <div class="mt-4">
                    <label for="confirm-email" class="block text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-1.5">
                        Type the account email to confirm
                    </label>
                    <input id="confirm-email" type="text" name="confirm_email" x-model="del.confirm"
                           autocomplete="off" spellcheck="false"
                           placeholder="name@example.com"
                           class="input input-bordered w-full h-11 rounded-lg text-sm">
                    <p class="mt-1.5 text-xs text-base-content/50">
                        Must match <span class="font-medium break-all" x-text="del.email"></span>
                    </p>
                    @error('confirm_email')
                        <p class="mt-1.5 text-xs text-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-4">
                    <label for="delete-reason" class="block text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-1.5">
                        Reason <span class="font-normal normal-case tracking-normal text-base-content/40">(optional, kept in the audit log)</span>
                    </label>
                    <input id="delete-reason" type="text" name="reason" x-model="del.reason"
                           maxlength="500" placeholder="e.g. duplicate account, employee left"
                           class="input input-bordered w-full h-11 rounded-lg text-sm">
                </div>

                <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                    <button type="button" x-on:click="$dispatch('close-modal', 'delete-user')"
                            class="btn btn-ghost btn-sm h-10">
                        Cancel
                    </button>
                    <button type="submit" :disabled="! canConfirm()"
                            class="btn btn-error btn-sm h-10 gap-1 disabled:opacity-40 disabled:cursor-not-allowed">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/></svg>
                        Delete permanently
                    </button>
                </div>
            </form>
        </x-modal>
    </div>
</x-app-layout>
