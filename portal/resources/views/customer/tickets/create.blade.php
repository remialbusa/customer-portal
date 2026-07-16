<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Submit a New Service Request
                </h2>
                <p class="text-sm text-gray-500 mt-1">Tell us what's happening — our team will take it from there.</p>
            </div>
            <a href="{{ route('dashboard') }}" class="text-sm text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
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
                <div class="mb-6 bg-amber-50 border border-amber-300 text-amber-900 rounded-lg px-5 py-4 text-sm">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-amber-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 6a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 6zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
                        <div class="flex-1">
                            <p class="font-semibold">
                                You already have {{ count($dupes) === 1 ? 'an open ticket' : count($dupes) . ' open tickets' }}
                                with the same subject.
                            </p>
                            <p class="mt-1 text-amber-800">
                                Please review the existing request{{ count($dupes) === 1 ? '' : 's' }} — our team may
                                already be working on it.
                            </p>
                            <ul class="mt-3 space-y-2">
                                @foreach ($dupes as $d)
                                    <li class="flex items-start gap-2 bg-white border border-amber-200 rounded-lg px-3 py-2">
                                        <span class="mt-0.5">🎫</span>
                                        <div class="flex-1 min-w-0">
                                            <a href="{{ route('tickets.show', ['id' => $d['id']]) }}"
                                               class="text-indigo-700 hover:text-indigo-900 font-medium break-words">
                                                #{{ $d['id'] }} — {{ $d['name'] }}
                                            </a>
                                            <div class="text-xs text-gray-600 mt-0.5 flex flex-wrap gap-x-3 gap-y-0.5">
                                                @if (! empty($d['status_text']))       <span>Status: <span class="font-medium">{{ $d['status_text'] }}</span></span> @endif
                                                @if (! empty($d['request_type_text'])) <span>Type: <span class="font-medium">{{ $d['request_type_text'] }}</span></span> @endif
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>

                            @if (count($dupes) >= 2)
                                <p class="mt-3 text-xs text-amber-800 italic">
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
                                    <button type="submit"
                                            class="inline-flex items-center px-3 py-1.5 bg-amber-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-700 transition">
                                        Submit anyway
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 bg-red-50 border border-red-200 text-red-800 rounded-lg px-5 py-4 text-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('tickets.store') }}" class="space-y-6">
                @csrf

                {{-- ───── Account info card ───── --}}
                <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100 flex items-center gap-4">
                        <div class="flex-shrink-0 w-11 h-11 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg font-semibold">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-semibold text-gray-900">{{ $user->name }}</div>
                            <div class="text-xs text-gray-500 flex flex-wrap items-center gap-x-2">
                                <span>{{ $user->account_name ?: 'Account not set' }}</span>
                                @if($user->branch)
                                    <span class="text-gray-300">·</span>
                                    <span>{{ $user->branch }}</span>
                                @endif
                                <span class="text-gray-300">·</span>
                                <span>{{ $user->email }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ───── Machine selector ─────
                     If the customer has registered machines, show a
                     dropdown of their machines. Otherwise fall back
                     to the free-text brand/model picker. --}}
                <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                            <span class="w-7 h-7 rounded-md bg-amber-50 text-amber-600 flex items-center justify-center text-sm">🧪</span>
                            Affected Equipment
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">Select the machine that needs service, or describe it manually.</p>
                    </div>
                    <div class="px-6 py-5">
                        @if ($machines->isNotEmpty())
                            {{-- Customer has registered machines — show dropdown --}}
                            <div x-data="{ machineId: '{{ old('machine_id', '') }}', showManual: {{ old('machine_id') ? 'false' : 'true' }} }">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Your registered equipment</label>
                                <div class="space-y-2">
                                    @foreach ($machines as $machine)
                                        <label class="flex items-center gap-3 px-4 py-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50">
                                            <input type="radio"
                                                   name="machine_id"
                                                   value="{{ $machine->id }}"
                                                   x-model="machineId"
                                                   @change="showManual = false"
                                                   class="text-indigo-600 focus:ring-indigo-500"
                                                   @checked(old('machine_id') == $machine->id)>
                                            <div class="flex-1 min-w-0">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $machine->brand }} {{ $machine->model }}
                                                    @if($machine->is_primary)
                                                        <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-indigo-100 text-indigo-700">Primary</span>
                                                    @endif
                                                </div>
                                                @if($machine->serial_number)
                                                    <div class="text-xs text-gray-500">S/N: {{ $machine->serial_number }}</div>
                                                @endif
                                                @if($machine->nickname)
                                                    <div class="text-xs text-gray-400">{{ $machine->nickname }}</div>
                                                @endif
                                            </div>
                                        </label>
                                    @endforeach

                                    {{-- Manual entry option --}}
                                    <label class="flex items-center gap-3 px-4 py-3 border border-dashed border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50">
                                        <input type="radio"
                                               name="machine_id"
                                               value=""
                                               x-model="machineId"
                                               @change="showManual = true"
                                               class="text-indigo-600 focus:ring-indigo-500"
                                               @checked(!old('machine_id') && $machines->isEmpty())>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-medium text-gray-700">Different machine (enter manually)</div>
                                        </div>
                                    </label>
                                </div>

                                {{-- Manual brand/model inputs --}}
                                <div x-show="showManual" x-cloak class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <label for="brand" class="block text-xs font-medium text-gray-600 mb-1">Brand</label>
                                        <input type="text" id="brand" name="brand" maxlength="120"
                                               value="{{ old('brand') }}"
                                               placeholder="e.g. Mindray"
                                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                    </div>
                                    <div>
                                        <label for="model" class="block text-xs font-medium text-gray-600 mb-1">Model</label>
                                        <input type="text" id="model" name="model" maxlength="120"
                                               value="{{ old('model') }}"
                                               placeholder="e.g. BC-6800"
                                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                    </div>
                                    <div>
                                        <label for="serial" class="block text-xs font-medium text-gray-600 mb-1">Serial #</label>
                                        <input type="text" id="serial" name="serial" maxlength="120"
                                               value="{{ old('serial') }}"
                                               placeholder="Optional"
                                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                    </div>
                                </div>
                            </div>
                        @else
                            {{-- No registered machines — show free-text inputs --}}
                            <p class="text-xs text-gray-500 mb-4">
                                You haven't registered any equipment yet. Please describe the machine that needs service.
                                <a href="{{ route('profile') }}" class="text-indigo-600 hover:text-indigo-800">Register equipment in your profile</a>
                                for faster ticket submission next time.
                            </p>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label for="brand" class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
                                    <input type="text" id="brand" name="brand" maxlength="120"
                                           value="{{ old('brand', $user->brand) }}"
                                           placeholder="e.g. Mindray"
                                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                </div>
                                <div>
                                    <label for="model" class="block text-sm font-medium text-gray-700 mb-1">Model</label>
                                    <input type="text" id="model" name="model" maxlength="120"
                                           value="{{ old('model', $user->model) }}"
                                           placeholder="e.g. BC-6800"
                                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                </div>
                                <div>
                                    <label for="serial" class="block text-sm font-medium text-gray-700 mb-1">Serial #</label>
                                    <input type="text" id="serial" name="serial" maxlength="120"
                                           value="{{ old('serial', $user->serial_number) }}"
                                           placeholder="Optional"
                                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- ───── Ticket details ───── --}}
                <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                            <span class="w-7 h-7 rounded-md bg-blue-50 text-blue-600 flex items-center justify-center text-sm">📋</span>
                            Request Details
                        </h3>
                    </div>
                    <div class="px-6 py-5 space-y-5">
                        <div>
                            <label for="subject" class="block text-sm font-medium text-gray-700 mb-1">
                                Subject <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="subject" id="subject" required maxlength="255"
                                   value="{{ old('subject') }}"
                                   placeholder="e.g. BC-6800 returns error code E-204 on startup"
                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm @error('subject') border-red-500 focus:border-red-500 focus:ring-red-500 @enderror">
                            @error('subject')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                                Description <span class="text-red-500">*</span>
                            </label>
                            <textarea name="description" id="description" rows="5" required maxlength="5000"
                                      placeholder="What happened? When did it start? Any error messages, unusual sounds, or smells?"
                                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm @error('description') border-red-500 focus:border-red-500 focus:ring-red-500 @enderror">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-400">Be as specific as you can — our technicians will read this first.</p>
                        </div>

                        <div>
                            <label for="request_type" class="block text-sm font-medium text-gray-700 mb-1">
                                Request Type <span class="text-red-500">*</span>
                            </label>
                            <select name="request_type" id="request_type" required
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm @error('request_type') border-red-500 focus:border-red-500 focus:ring-red-500 @enderror">
                                <option value="">Select type…</option>
                                @foreach($requestTypes as $rt)
                                    <option value="{{ $rt }}" @selected(old('request_type') === $rt)>{{ $rt }}</option>
                                @endforeach
                            </select>
                            @error('request_type')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- ───── Submit ───── --}}
                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('dashboard') }}"
                       class="inline-flex items-center px-4 py-2.5 bg-white border border-gray-300 rounded-lg font-medium text-sm text-gray-700 shadow-sm hover:bg-gray-50 transition">
                        Cancel
                    </a>
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 border border-transparent rounded-lg font-medium text-sm text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
