@php
    $roleLabels = [
        'customer'   => 'Customer',
        'fse'        => 'Field engineer',
        'its'        => 'IT specialist',
        'manager'    => 'Manager',
        'admin'      => 'Admin',
        'superadmin' => 'Superadmin',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <p class="text-xs font-semibold tracking-widest uppercase text-base-content/50 mb-1">
                    Administration
                </p>
                <h2 class="font-semibold text-2xl text-base-content leading-tight">
                    Edit account
                </h2>
                <p class="text-sm text-base-content/60 mt-1 break-all">
                    {{ $target->email }}
                </p>
            </div>
            <a href="{{ route('admin.users.index') }}" wire:navigate class="btn btn-ghost btn-sm gap-1 self-start sm:self-auto">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                All users
            </a>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-3xl mx-auto sm:px-4 lg:px-6 space-y-6">

            @if ($errors->any())
                <div role="alert" class="alert alert-error shadow-sm flex-col items-start gap-1 p-4">
                    <h3 class="font-semibold text-sm">Please fix the following</h3>
                    <ul class="list-disc list-inside text-xs space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- ── Status + quick action ─────────────────────────── --}}
            <x-ui.card padding="p-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-2.5">
                        <span class="badge {{ $target->status === 'active' ? 'badge-success' : 'badge-warning' }} badge-sm">
                            {{ ucfirst($target->status) }}
                        </span>
                        <p class="text-sm text-base-content/70">
                            @if ($target->status === 'active')
                                This account can sign in normally.
                            @else
                                Blocked from signing in. Nothing has been deleted.
                            @endif
                        </p>
                    </div>

                    @if ($target->status === 'suspended')
                        <form method="POST" action="{{ route('admin.users.reactivate', $target) }}">
                            @csrf
                            <button type="submit" class="btn btn-outline btn-sm h-10 gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Reactivate
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.users.suspend', $target) }}"
                              onsubmit="return confirm('Suspend {{ $target->email }}? They will not be able to sign in. Nothing is deleted, so this can be undone.');">
                            @csrf
                            <button type="submit" class="btn btn-outline btn-sm h-10 gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                Suspend
                            </button>
                        </form>
                    @endif
                </div>
            </x-ui.card>

            {{-- ── Edit form ─────────────────────────────────────── --}}
            <form method="POST" action="{{ route('admin.users.update', $target) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <x-ui.card title="Identity" subtitle="Who this account belongs to" padding="p-5">
                    <div class="space-y-4">
                        <div>
                            <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-1.5">
                                Full name <span class="text-error">*</span>
                            </label>
                            <input id="name" type="text" name="name" required maxlength="255"
                                   value="{{ old('name', $target->name) }}"
                                   class="input input-bordered w-full h-11 rounded-lg text-sm @error('name') input-error @enderror">
                            @error('name')
                                <p class="mt-1.5 text-xs text-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-1.5">
                                Email <span class="text-error">*</span>
                            </label>
                            <input id="email" type="email" name="email" required maxlength="255"
                                   value="{{ old('email', $target->email) }}" autocomplete="off" spellcheck="false"
                                   class="input input-bordered w-full h-11 rounded-lg text-sm @error('email') input-error @enderror">
                            @error('email')
                                <p class="mt-1.5 text-xs text-error">{{ $message }}</p>
                            @enderror
                            <p class="mt-1.5 text-xs text-base-content/50">
                                This is the sign-in address. Changing it here does not change the Monday board.
                            </p>
                        </div>

                        <div>
                            <label for="role" class="block text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-1.5">
                                Role <span class="text-error">*</span>
                            </label>
                            <select id="role" name="role" required
                                    class="select select-bordered w-full h-11 rounded-lg text-sm @error('role') select-error @enderror">
                                @foreach ($roles as $role)
                                    <option value="{{ $role }}" @selected(old('role', $target->role) === $role)>
                                        {{ $roleLabels[$role] ?? $role }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role')
                                <p class="mt-1.5 text-xs text-error">{{ $message }}</p>
                            @enderror
                            <p class="mt-1.5 text-xs text-base-content/50">
                                Only operational roles can be assigned. Admin and superadmin accounts are managed in the database.
                            </p>
                        </div>
                    </div>
                </x-ui.card>

                <x-ui.card title="Placement" subtitle="Region, hospital, and contact details" padding="p-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="region" class="block text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-1.5">
                                Region
                            </label>
                            <input id="region" type="text" name="region" maxlength="100"
                                   value="{{ old('region', $target->region) }}"
                                   placeholder="e.g. NLuzon"
                                   class="input input-bordered w-full h-11 rounded-lg text-sm @error('region') input-error @enderror">
                            @error('region')
                                <p class="mt-1.5 text-xs text-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="team" class="block text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-1.5">
                                Team
                            </label>
                            <input id="team" type="text" name="team" maxlength="100"
                                   value="{{ old('team', $target->team) }}"
                                   class="input input-bordered w-full h-11 rounded-lg text-sm @error('team') input-error @enderror">
                            @error('team')
                                <p class="mt-1.5 text-xs text-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label for="account_name" class="block text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-1.5">
                                Hospital / account name
                            </label>
                            <input id="account_name" type="text" name="account_name" maxlength="255"
                                   value="{{ old('account_name', $target->account_name) }}"
                                   class="input input-bordered w-full h-11 rounded-lg text-sm @error('account_name') input-error @enderror">
                            @error('account_name')
                                <p class="mt-1.5 text-xs text-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="branch" class="block text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-1.5">
                                Branch
                            </label>
                            <input id="branch" type="text" name="branch" maxlength="255"
                                   value="{{ old('branch', $target->branch) }}"
                                   class="input input-bordered w-full h-11 rounded-lg text-sm @error('branch') input-error @enderror">
                            @error('branch')
                                <p class="mt-1.5 text-xs text-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="address" class="block text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-1.5">
                                Address
                            </label>
                            <input id="address" type="text" name="address" maxlength="500"
                                   value="{{ old('address', $target->address) }}"
                                   class="input input-bordered w-full h-11 rounded-lg text-sm @error('address') input-error @enderror">
                            @error('address')
                                <p class="mt-1.5 text-xs text-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </x-ui.card>

                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                    <a href="{{ route('admin.users.index') }}" wire:navigate class="btn btn-ghost btn-sm h-11">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary btn-sm h-11">
                        Save changes
                    </button>
                </div>
            </form>

            <p class="text-xs text-base-content/50">
                Permanently deleting an account is done from
                <a href="{{ route('admin.users.index') }}" wire:navigate class="link link-primary">the users list</a>,
                where it asks you to type the email to confirm.
            </p>
        </div>
    </div>
</x-app-layout>
