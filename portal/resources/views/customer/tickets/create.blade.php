<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <p class="text-xs font-semibold tracking-widest uppercase text-base-content/50 mb-1">
                    New service request
                </p>
                <h2 class="font-semibold text-2xl text-base-content leading-tight">
                    Tell us what's happening
                </h2>
                <p class="text-sm text-base-content/60 mt-1">
                    Our team will take it from there. We usually reply within a few hours during business days.
                </p>
            </div>
            <a href="{{ route('dashboard') }}" class="btn btn-ghost btn-sm gap-2 self-start">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

            {{-- ───── Existing-ticket warning ───── --}}
            @if (session('duplicate_tickets'))
                @php $dupes = session('duplicate_tickets'); @endphp
                <x-ui.card tone="warning" padding="p-5">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-full bg-warning/15 text-warning flex items-center justify-center text-xl flex-shrink-0" aria-hidden="true">
                            !
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold text-base-content">
                                You already have {{ count($dupes) === 1 ? 'an open ticket' : count($dupes) . ' open tickets' }}
                                with the same subject.
                            </h3>
                            <p class="mt-1 text-sm text-base-content/70">
                                Please review the existing request{{ count($dupes) === 1 ? '' : 's' }} — our team may
                                already be working on it.
                            </p>
                            <ul class="mt-3 space-y-2">
                                @foreach ($dupes as $d)
                                    <li class="flex items-start gap-2 bg-base-100 border border-warning/30 rounded-lg px-3 py-2">
                                        <span class="mt-0.5">🎫</span>
                                        <div class="flex-1 min-w-0">
                                            <a href="{{ route('tickets.show', ['id' => $d['id']]) }}"
                                               class="text-primary hover:text-primary/80 font-medium break-words">
                                                #{{ $d['id'] }} — {{ $d['name'] }}
                                            </a>
                                            <div class="text-xs text-base-content/60 mt-0.5 flex flex-wrap gap-x-3 gap-y-0.5">
                                                @if (! empty($d['status_text']))       <span>Status: <span class="font-medium">{{ $d['status_text'] }}</span></span> @endif
                                                @if (! empty($d['request_type_text'])) <span>Type: <span class="font-medium">{{ $d['request_type_text'] }}</span></span> @endif
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>

                            @if (count($dupes) >= 2)
                                <p class="mt-3 text-xs text-base-content/60 italic">
                                    You already have {{ count($dupes) }} open tickets with this subject.
                                    Please contact our support team to consolidate them before submitting a new one.
                                </p>
                            @else
                                <form method="POST"
                                      action="{{ route('tickets.store', ['force' => 1]) }}"
                                      class="mt-3 flex items-center justify-end gap-2">
                                    @csrf
                                    <input type="hidden" name="subject"      value="{{ old('subject') }}">
                                    <input type="hidden" name="description"  value="{{ old('description') }}">
                                    <input type="hidden" name="request_type" value="{{ old('request_type') }}">
                                    <input type="hidden" name="machine_id"   value="{{ old('machine_id') }}">
                                    <input type="hidden" name="brand"        value="{{ old('brand') }}">
                                    <input type="hidden" name="model"        value="{{ old('model') }}">
                                    <input type="hidden" name="serial"       value="{{ old('serial') }}">
                                    @foreach (old('assigned_tsp_ids', []) as $tid)
                                        <input type="hidden" name="assigned_tsp_ids[]" value="{{ $tid }}">
                                    @endforeach
                                    <button type="submit" class="btn btn-warning btn-sm">
                                        Submit anyway
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </x-ui.card>
            @endif

            @if ($errors->any())
                <x-ui.toast type="error" title="Please fix the following">
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-ui.toast>
            @endif

            <form method="POST" action="{{ route('tickets.store') }}" class="space-y-6">
                @csrf

                {{-- ───── Account info card ───── --}}
                <x-ui.card padding="p-5">
                    <div class="flex items-center gap-4">
                        <div class="flex-shrink-0 w-12 h-12 rounded-full bg-primary text-primary-content flex items-center justify-center text-lg font-semibold">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-semibold text-base-content">{{ $user->name }}</div>
                            <div class="text-xs text-base-content/60 flex flex-wrap items-center gap-x-2 mt-0.5">
                                <span>{{ $user->account_name ?: 'Account not set' }}</span>
                                @if($user->branch)
                                    <span class="text-base-content/30">·</span>
                                    <span>{{ $user->branch }}</span>
                                @endif
                                <span class="text-base-content/30">·</span>
                                <span>{{ $user->email }}</span>
                            </div>
                        </div>
                    </div>
                </x-ui.card>

                {{-- ───── Machine selector ─────
                     If the customer has registered machines, show a
                     dropdown of their machines. Otherwise fall back
                     to the free-text brand/model picker. --}}
                <x-ui.card padding="p-0"
                    icon="🧪"
                    title="Affected equipment"
                    subtitle="Select the machine that needs service, or describe it manually."
                >
                    <div class="px-5 py-4">
                        @if ($machines->isNotEmpty())
                            {{-- Customer has registered machines — show dropdown --}}
                            <div x-data="{ machineId: '{{ old('machine_id', '') }}', showManual: {{ old('machine_id') ? 'false' : 'true' }} }">
                                <label class="block text-sm font-medium text-base-content mb-2">Your registered equipment</label>
                                <div class="space-y-2">
                                    @foreach ($machines as $machine)
                                        <label class="flex items-center gap-3 px-4 py-3 border border-base-300 rounded-lg cursor-pointer hover:bg-base-200/60 transition has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                                            <input type="radio"
                                                   name="machine_id"
                                                   value="{{ $machine->id }}"
                                                   x-model="machineId"
                                                   @change="showManual = false"
                                                   class="radio radio-primary radio-sm"
                                                   @checked(old('machine_id') == $machine->id)>
                                            <div class="flex-1 min-w-0">
                                                <div class="text-sm font-medium text-base-content">
                                                    {{ $machine->brand }} {{ $machine->model }}
                                                    @if($machine->is_primary)
                                                        <span class="badge badge-primary badge-sm ml-1">Primary</span>
                                                    @endif
                                                </div>
                                                @if($machine->serial_number)
                                                    <div class="text-xs text-base-content/60">S/N: {{ $machine->serial_number }}</div>
                                                @endif
                                                @if($machine->nickname)
                                                    <div class="text-xs text-base-content/40">{{ $machine->nickname }}</div>
                                                @endif
                                            </div>
                                        </label>
                                    @endforeach

                                    {{-- Manual entry option --}}
                                    <label class="flex items-center gap-3 px-4 py-3 border border-dashed border-base-300 rounded-lg cursor-pointer hover:bg-base-200/60 transition has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                                        <input type="radio"
                                               name="machine_id"
                                               value=""
                                               x-model="machineId"
                                               @change="showManual = true"
                                               class="radio radio-primary radio-sm"
                                               @checked(!old('machine_id') && $machines->isEmpty())>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-medium text-base-content/80">Different machine (enter manually)</div>
                                        </div>
                                    </label>
                                </div>

                                {{-- Manual brand/model inputs --}}
                                <div x-show="showManual" x-cloak class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <label for="brand" class="block text-xs font-medium text-base-content/70 mb-1">Brand</label>
                                        <input type="text" id="brand" name="brand" maxlength="120"
                                               value="{{ old('brand') }}"
                                               placeholder="e.g. Mindray"
                                               class="input input-bordered input-sm w-full">
                                    </div>
                                    <div>
                                        <label for="model" class="block text-xs font-medium text-base-content/70 mb-1">Model</label>
                                        <input type="text" id="model" name="model" maxlength="120"
                                               value="{{ old('model') }}"
                                               placeholder="e.g. BC-6800"
                                               class="input input-bordered input-sm w-full">
                                    </div>
                                    <div>
                                        <label for="serial" class="block text-xs font-medium text-base-content/70 mb-1">Serial #</label>
                                        <input type="text" id="serial" name="serial" maxlength="120"
                                               value="{{ old('serial') }}"
                                               placeholder="Optional"
                                               class="input input-bordered input-sm w-full">
                                    </div>
                                </div>
                            </div>
                        @else
                            {{-- No registered machines — show free-text inputs --}}
                            <div class="alert bg-base-200 border border-base-300 text-base-content/80 rounded-xl mb-4">
                                <span aria-hidden="true" class="text-xl">💡</span>
                                <div class="text-sm">
                                    You haven't registered any equipment yet. Please describe the machine that needs service.
                                    <a href="{{ route('profile') }}" class="link link-primary">Register equipment in your profile</a>
                                    for faster ticket submission next time.
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label for="brand" class="block text-sm font-medium text-base-content mb-1">Brand</label>
                                    <input type="text" id="brand" name="brand" maxlength="120"
                                           value="{{ old('brand', $user->brand) }}"
                                           placeholder="e.g. Mindray"
                                           class="input input-bordered w-full">
                                </div>
                                <div>
                                    <label for="model" class="block text-sm font-medium text-base-content mb-1">Model</label>
                                    <input type="text" id="model" name="model" maxlength="120"
                                           value="{{ old('model', $user->model) }}"
                                           placeholder="e.g. BC-6800"
                                           class="input input-bordered w-full">
                                </div>
                                <div>
                                    <label for="serial" class="block text-sm font-medium text-base-content mb-1">Serial #</label>
                                    <input type="text" id="serial" name="serial" maxlength="120"
                                           value="{{ old('serial', $user->serial_number) }}"
                                           placeholder="Optional"
                                           class="input input-bordered w-full">
                                </div>
                            </div>
                        @endif
                    </div>
                </x-ui.card>

                {{-- ───── Preferred TSPs ─────
                     Customer picks which on-site technicians / IT
                     specialists they'd like assigned to the ticket.
                     The list is scoped to the customer's region (when
                     we can resolve one) so the picker shows the team
                     physically closest to them. Region resolution
                     happens in TicketController::create() via
                     RegionResolver, which inspects `users.region`,
                     `users.branch`, and `users.address` in that order.

                     When we can't resolve a region, we fall back to
                     showing all 4 region groups so the customer can
                     still pick someone. Members without a Monday
                     person id (so we can't assign them via the People
                     column) are listed but disabled. --}}
                @php
                    // Decode the previous selection back to the
                    // checkbox state when re-rendering after a
                    // validation error. old() returns null when the
                    // form was submitted with the field empty.
                    $oldTsp = collect(old('assigned_tsp_ids', []))
                        ->map(fn ($v) => (int) $v)
                        ->all();
                    $oldTsp = array_flip($oldTsp);
                    $isScoped = $tspDirectory->contains(fn ($g) => $g['scoped'] ?? false);
                    $regionLabel = $customerRegion
                        ? (\App\Support\PersonnelDirectory::REGION_LABELS[$customerRegion] ?? $customerRegion)
                        : null;
                @endphp
                <x-ui.card padding="p-0"
                    icon="🧑‍🔧"
                    title="Preferred technicians"
                    subtitle="Optional — pick the field engineers or IT specialists you'd like assigned. Leave blank if you have no preference and we'll route it to the right team."
                    x-data='{ selected: @js((int) count($oldTsp)), clearAll() { const inputs = this.$refs.form.querySelectorAll("input[type=checkbox]"); inputs.forEach(el => { if (el.name && el.name.indexOf("assigned_tsp_ids") === 0) { el.checked = false; } }); this.selected = 0; } }'
                >
                    @if ($isScoped && $regionLabel)
                        <p class="text-xs text-secondary px-5 pt-3 pb-1 flex items-start gap-1.5">
                            <svg class="w-3.5 h-3.5 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                            Showing technicians in <span class="font-semibold">{{ $regionLabel }}</span> based on your registered branch / address.
                        </p>
                    @elseif (! $isScoped)
                        <div class="alert bg-warning/10 border border-warning/30 text-base-content/80 rounded-xl py-2 px-3 mx-5 mt-3">
                            <span aria-hidden="true" class="text-base">⚠</span>
                            <div class="text-xs">
                                We couldn't determine your area from your profile — showing all available technicians.
                                <a href="{{ route('profile') }}" class="link link-primary">Update your branch / address</a>
                                to see only those closest to you.
                            </div>
                        </div>
                    @endif
                    <div class="px-5 py-4" x-ref="form">
                        <div class="flex items-center justify-between mb-4">
                            <p class="text-xs text-base-content/60">
                                <span x-text="selected"></span> selected
                            </p>
                            <button type="button"
                                    @click="clearAll()"
                                    x-show="selected > 0"
                                    class="text-xs text-base-content/60 hover:text-base-content underline">
                                Clear all
                            </button>
                        </div>

                        @if ($tspDirectory->every(fn ($g) => $g['members']->isEmpty()))
                            @if ($isScoped)
                                <p class="text-sm text-base-content/60 italic">
                                    No field engineers or IT specialists are currently assigned to your area
                                    @if ($regionLabel)({{ $regionLabel }})@endif.
                                    Your ticket will still be created — it will be auto-routed to the nearest available team.
                                </p>
                            @else
                                <p class="text-sm text-base-content/60 italic">
                                    No field engineers or IT specialists are currently configured.
                                    Your ticket will still be created — it will be auto-routed to the on-call team.
                                </p>
                            @endif
                        @else
                            <div class="space-y-5">
                                @foreach ($tspDirectory as $group)
                                    @if ($group['members']->isNotEmpty())
                                        <div>
                                            <h4 class="text-xs font-semibold text-base-content/60 uppercase tracking-wider mb-2 flex items-center gap-2">
                                                <span class="inline-block w-1.5 h-1.5 rounded-full bg-secondary"></span>
                                                {{ $group['label'] }}
                                                <span class="text-base-content/40 normal-case font-normal">
                                                    ({{ $group['members']->count() }})
                                                </span>
                                            </h4>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                @foreach ($group['members'] as $m)
                                                    <label class="flex items-start gap-2 px-3 py-2 border border-base-300 rounded-lg cursor-pointer hover:bg-base-200/60 transition has-[:checked]:border-secondary has-[:checked]:bg-secondary/10 {{ $m['assignable'] ? '' : 'opacity-50 cursor-not-allowed' }}">
                                                        <input type="checkbox"
                                                               name="assigned_tsp_ids[]"
                                                               value="{{ $m['id'] }}"
                                                               @checked(isset($oldTsp[$m['id']]))
                                                               @disabled(! $m['assignable'])
                                                               @change="selected += $event.target.checked ? 1 : -1"
                                                               class="checkbox checkbox-sm checkbox-secondary mt-0.5 disabled:opacity-50">
                                                        <div class="flex-1 min-w-0">
                                                            <div class="text-sm font-medium text-base-content truncate">
                                                                {{ $m['name'] }}
                                                            </div>
                                                            <div class="text-xs text-base-content/60 flex items-center gap-1.5 mt-0.5 flex-wrap">
                                                                <span class="badge badge-sm {{ $m['role'] === 'fse' ? 'badge-warning' : 'badge-info' }} badge-outline">
                                                                    @php
                                                                        $roleFullName = match($m['role']) {
                                                                            'fse' => 'Field Service Engineer',
                                                                            'its' => 'IT Specialist',
                                                                            default => strtoupper($m['role']),
                                                                        };
                                                                    @endphp
                                                                    {{ $roleFullName }}{{ $m['team'] && str_contains($m['team'], 'Sr') ? ' · Sr' : '' }}
                                                                </span>
                                                                @if (! $m['assignable'])
                                                                    <span class="text-base-content/40 italic">unavailable</span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                </x-ui.card>

                {{-- ───── Request details ───── --}}
                <x-ui.card padding="p-0"
                    icon="📋"
                    title="Request details"
                >
                    <div class="px-5 py-4 space-y-4">
                        <div>
                            <label for="subject" class="block text-sm font-medium text-base-content mb-1">
                                Subject <span class="text-error">*</span>
                            </label>
                            <input type="text" name="subject" id="subject" required maxlength="255"
                                   value="{{ old('subject') }}"
                                   placeholder="e.g. BC-6800 returns error code E-204 on startup"
                                   class="input input-bordered w-full @error('subject') input-error @enderror">
                            @error('subject')
                                <p class="mt-1 text-xs text-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-base-content mb-1">
                                Description <span class="text-error">*</span>
                            </label>
                            <textarea name="description" id="description" rows="5" required maxlength="5000"
                                      placeholder="What happened? When did it start? Any error messages, unusual sounds, or smells?"
                                      class="textarea textarea-bordered w-full @error('description') textarea-error @enderror">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="mt-1 text-xs text-error">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-base-content/50">Be as specific as you can — our technicians will read this first.</p>
                        </div>

                        <div>
                            <label for="request_type" class="block text-sm font-medium text-base-content mb-1">
                                Request type <span class="text-error">*</span>
                            </label>
                            <select name="request_type" id="request_type" required
                                    class="select select-bordered w-full @error('request_type') select-error @enderror">
                                <option value="">Select type…</option>
                                @foreach($requestTypes as $rt)
                                    <option value="{{ $rt }}" @selected(old('request_type') === $rt)>{{ $rt }}</option>
                                @endforeach
                            </select>
                            @error('request_type')
                                <p class="mt-1 text-xs text-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </x-ui.card>

                {{-- ───── Submit ───── --}}
                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('dashboard') }}" class="btn btn-ghost">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        Submit request
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
