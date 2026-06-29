<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                New Ticket
            </h2>
            <a href="{{ route('dashboard') }}" class="text-sm text-indigo-600 hover:text-indigo-800">
                &larr; Back to tickets
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg">

                {{-- ───── Existing-ticket warning ─────
                     Rendered only when the controller detected a
                     duplicate subject on an open ticket for this
                     customer. Shows the matching ticket(s) with a
                     link to each, and a small "Submit anyway" form
                     that bypasses the guard by posting with
                     ?force=1. The form's hidden inputs replay the
                     user's in-progress input so they don't lose
                     what they typed. --}}
                @if (session('duplicate_tickets'))
                    @php $dupes = session('duplicate_tickets'); @endphp
                    <div class="m-6 mb-0 bg-amber-50 border border-amber-300 text-amber-900 rounded px-4 py-3 text-sm">
                        <p class="font-semibold">
                            You already have {{ count($dupes) === 1 ? 'an open ticket' : count($dupes) . ' open tickets' }}
                            with the same subject.
                        </p>
                        <p class="mt-1">
                            Please review the existing request{{ count($dupes) === 1 ? '' : 's' }} below — our team may
                            already be working on it. If you still need to file a new one, use the
                            <span class="font-medium">"Submit anyway"</span> button at the bottom of this notice.
                        </p>
                        <ul class="mt-3 space-y-2">
                            @foreach ($dupes as $d)
                                <li class="flex items-start gap-2 bg-white border border-amber-200 rounded px-3 py-2">
                                    <span class="mt-0.5">🎫</span>
                                    <div class="flex-1 min-w-0">
                                        <a href="{{ route('tickets.show', ['id' => $d['id']]) }}"
                                           class="text-indigo-700 hover:text-indigo-900 font-medium break-words">
                                            #{{ $d['id'] }} — {{ $d['name'] }}
                                        </a>
                                        <div class="text-xs text-gray-600 mt-0.5 flex flex-wrap gap-x-3 gap-y-0.5">
                                            @if (! empty($d['status_text']))     <span>Status: <span class="font-medium">{{ $d['status_text'] }}</span></span> @endif
                                            @if (! empty($d['priority_text']))   <span>Priority: <span class="font-medium">{{ $d['priority_text'] }}</span></span> @endif
                                            @if (! empty($d['request_type_text'])) <span>Type: <span class="font-medium">{{ $d['request_type_text'] }}</span></span> @endif
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>

                        @if (count($dupes) >= 2)
                            {{-- Hard cap: when 2 or more duplicates already exist,
                                 the bypass is removed entirely. The user must
                                 contact support to file a new ticket with the
                                 same subject. This prevents runaway duplicate
                                 creation from mis-clicks, double-submits, or
                                 intentional abuse. --}}
                            <p class="mt-3 text-xs text-amber-800 italic">
                                You already have {{ count($dupes) }} open tickets with this subject.
                                To file a new one, please contact our support team directly so we can
                                consolidate or close the existing request{{ count($dupes) === 1 ? '' : 's' }} first.
                            </p>
                        @else
                            <form method="POST"
                                  action="{{ route('tickets.store', ['force' => 1]) }}"
                                  class="mt-3 flex items-center justify-end gap-2">
                                @csrf
                                {{-- Replay the user's in-progress input so the
                                     submit-anyway path doesn't lose their text. --}}
                                <input type="hidden" name="subject"      value="{{ old('subject') }}">
                                <input type="hidden" name="description"  value="{{ old('description') }}">
                                <input type="hidden" name="request_type" value="{{ old('request_type') }}">
                                <input type="hidden" name="priority"     value="{{ old('priority', 'Medium') }}">
                                <input type="hidden" name="brand"        value="{{ old('brand') }}">
                                <input type="hidden" name="model"        value="{{ old('model') }}">
                                <input type="hidden" name="serial"       value="{{ old('serial') }}">
                                <button type="submit"
                                        class="inline-flex items-center px-3 py-1.5 bg-amber-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-700 focus:bg-amber-700 active:bg-amber-900 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition">
                                    Submit anyway
                                </button>
                            </form>
                        @endif
                    </div>
                @endif

                    @if ($errors->any())
                        <div class="bg-red-50 border border-red-200 text-red-800 rounded px-4 py-3 text-sm">
                            <ul class="list-disc list-inside space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('tickets.store') }}" class="space-y-6 p-6">
                        @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Account</label>
                        <div class="mt-1 text-sm text-gray-600 bg-gray-50 border border-gray-200 rounded px-3 py-2">
                            {{ $user->account_name ?: '—' }}
                            @if($user->branch) <span class="text-gray-400"> &middot; </span> {{ $user->branch }} @endif
                            <span class="text-gray-400"> &middot; </span> {{ $user->email }}
                        </div>
                    </div>

                    <div>
                        <label for="subject" class="block text-sm font-medium text-gray-700">
                            Subject <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="subject" id="subject" required maxlength="255"
                               value="{{ old('subject') }}"
                               placeholder="e.g. BC-6800 returns error code E-204 on startup"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('subject') border-red-500 focus:border-red-500 focus:ring-red-500 @enderror">
                        @error('subject')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700">
                            Description <span class="text-red-500">*</span>
                        </label>
                        <textarea name="description" id="description" rows="6" required maxlength="5000"
                                  placeholder="What happened? When did it start? Any error messages, sounds, smells?"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('description') border-red-500 focus:border-red-500 focus:ring-red-500 @enderror">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">Be as specific as you can. Our TSPs will read this first.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="request_type" class="block text-sm font-medium text-gray-700">
                                Type <span class="text-red-500">*</span>
                            </label>
                            <select name="request_type" id="request_type" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('request_type') border-red-500 focus:border-red-500 focus:ring-red-500 @enderror">
                                <option value="">Select…</option>
                                @foreach($requestTypes as $rt)
                                    <option value="{{ $rt }}" @selected(old('request_type') === $rt)>{{ $rt }}</option>
                                @endforeach
                            </select>
                            @error('request_type')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="priority" class="block text-sm font-medium text-gray-700">
                                Priority <span class="text-red-500">*</span>
                            </label>
                            <select name="priority" id="priority" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('priority') border-red-500 focus:border-red-500 focus:ring-red-500 @enderror">
                                <option value="">Select…</option>
                                @foreach($priorities as $p)
                                    <option value="{{ $p }}" @selected(old('priority', 'Medium') === $p)>{{ $p }}</option>
                                @endforeach
                            </select>
                            @error('priority')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <fieldset class="border-t border-gray-200 pt-5">
                        <legend class="text-sm font-medium text-gray-700">Equipment details</legend>
                        <p class="text-xs text-gray-500 mt-1 mb-3">
                            Pre-filled from your profile when available. Update if the issue is with a different machine.
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label for="brand" class="block text-sm font-medium text-gray-700">Brand</label>
                                <input type="text" name="brand" id="brand" maxlength="120"
                                       value="{{ old('brand', $user->brand) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>
                            <div>
                                <label for="model" class="block text-sm font-medium text-gray-700">Model</label>
                                <input type="text" name="model" id="model" maxlength="120"
                                       value="{{ old('model', $user->model) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>
                            <div>
                                <label for="serial" class="block text-sm font-medium text-gray-700">Serial #</label>
                                <input type="text" name="serial" id="serial" maxlength="120"
                                       value="{{ old('serial', $user->serial_number) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>
                        </div>
                    </fieldset>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <a href="{{ route('dashboard') }}"
                           class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
                            Cancel
                        </a>
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
                            Submit Ticket
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
