<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Customer invites
            </h2>
            <a href="{{ route('admin.kpi') }}"
               class="text-sm text-gray-500 hover:text-gray-700 underline-offset-2 hover:underline">
                ← Back to KPI
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- ───────  Success banner with the issued link  ─────── --}}
            @if (session('invite_url'))
                <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4">
                    <p class="text-sm font-semibold text-emerald-800">
                        {{ session('status') }}
                    </p>
                    <p class="mt-1 text-xs text-emerald-700">
                        Expires {{ session('invite_expires_at') }}. Single use. Send this link to the customer:
                    </p>
                    <div class="mt-3 flex items-stretch gap-2">
                        <input
                            type="text"
                            readonly
                            value="{{ session('invite_url') }}"
                            class="flex-1 rounded border border-emerald-200 bg-white px-3 py-2 text-xs font-mono text-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-400"
                            onclick="this.select()"
                        />
                        <button type="button"
                                onclick="navigator.clipboard.writeText('{{ session('invite_url') }}'); this.textContent='Copied!'; setTimeout(()=>this.textContent='Copy',1500);"
                                class="rounded bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">
                            Copy
                        </button>
                    </div>
                </div>
            @endif

            {{-- ───────  Issue a new invite  ─────── --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900">Issue a new invite</h3>
                <p class="mt-1 text-sm text-gray-500">
                    The customer's email must already exist on the
                    <span class="font-medium">Customer Details</span> board on monday.com.
                    We'll look them up, snapshot their account / branch / region / address,
                    and print a one-time link valid for the chosen number of days.
                </p>

                <form method="POST" action="{{ route('admin.invites.store') }}" class="mt-6 space-y-5">
                    @csrf

                    <div>
                        <x-input-label for="email" value="Customer email" />
                        <x-text-input
                            id="email"
                            name="email"
                            type="email"
                            required
                            autofocus
                            placeholder="jane@stlukes.com"
                            value="{{ old('email') }}"
                            class="mt-1 block w-full"
                        />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="ttl" value="Link lifetime (days)" />
                            <x-text-input
                                id="ttl"
                                name="ttl"
                                type="number"
                                min="1"
                                max="365"
                                value="{{ old('ttl', $defaultTtl) }}"
                                class="mt-1 block w-full"
                            />
                            <p class="mt-1 text-xs text-gray-500">Default {{ $defaultTtl }} days.</p>
                            <x-input-error :messages="$errors->get('ttl')" class="mt-2" />
                        </div>
                        <div class="flex items-end">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input
                                    type="hidden"
                                    name="invalidate_existing"
                                    value="0"
                                />
                                <input
                                    type="checkbox"
                                    name="invalidate_existing"
                                    value="1"
                                    {{ old('invalidate_existing') ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                />
                                <span>Revoke any prior unused links for this email</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <x-primary-button>Issue invite</x-primary-button>
                        <span class="text-xs text-gray-500">
                            Logged in as
                            <span class="font-semibold text-gray-700">{{ $user->name }}</span>
                            ·
                            <span class="uppercase tracking-wide">{{ $user->role }}</span>
                        </span>
                    </div>
                </form>
            </div>

            {{-- ───────  Recent invites  ─────── --}}
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Recent invites</h3>
                    <p class="mt-1 text-xs text-gray-500">Last 25 issued, newest first.</p>
                </div>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider text-xs">Email</th>
                            <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider text-xs">Account</th>
                            <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider text-xs">Region / Branch</th>
                            <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider text-xs">Expires</th>
                            <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider text-xs">Status</th>
                            <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider text-xs">Issued by</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($recent as $invite)
                            @php
                                $status = $invite->isUsed()
                                    ? 'used'
                                    : ($invite->isExpired() ? 'expired' : 'active');
                                $statusClass = match ($status) {
                                    'used'    => 'bg-emerald-100 text-emerald-800',
                                    'expired' => 'bg-rose-100 text-rose-800',
                                    default   => 'bg-sky-100 text-sky-800',
                                };
                            @endphp
                            <tr>
                                <td class="px-4 py-2 font-mono text-xs text-gray-900">{{ $invite->email }}</td>
                                <td class="px-4 py-2 text-gray-700">{{ $invite->account_name ?: '—' }}</td>
                                <td class="px-4 py-2 text-gray-700">
                                    {{ $invite->region ?: '—' }}
                                    @if ($invite->branch)
                                        <span class="text-gray-400">·</span>
                                        <span class="text-xs text-gray-500">{{ $invite->branch }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-xs text-gray-600">
                                    {{ optional($invite->expires_at)->toDayDateTimeString() ?? '—' }}
                                </td>
                                <td class="px-4 py-2">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $statusClass }}">
                                        {{ ucfirst($status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-xs text-gray-600">
                                    {{ $invite->invitedBy?->name ?? '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500">
                                    No invites issued yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
