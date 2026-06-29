@php
    /** @var \App\Models\User $user */
    $user = auth()->user();
    $pendingRequest = \App\Models\AccountDeletionRequest::latestPendingFor($user->id);
@endphp

<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Delete Account') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Account deletion is handled by our superadmin team to keep your ticket history, chat logs, and audit trail safe. Submit a request below and they will review it and confirm by email.') }}
        </p>
    </header>

    @if (session('status'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-md px-4 py-3 text-sm">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 rounded-md px-4 py-3 text-sm">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($pendingRequest)
        {{-- A request is already in flight. Show its status and
             give the user a way to cancel if they filed by mistake. --}}
        <div class="bg-amber-50 border border-amber-200 text-amber-900 rounded-md p-4 text-sm space-y-3">
            <p>
                <span class="font-semibold">Request pending.</span>
                You submitted an account-deletion request on
                {{ $pendingRequest->created_at->format('M j, Y g:i A') }}.
                A superadmin will review it shortly.
            </p>
            @if ($pendingRequest->reason)
                <p class="text-xs">
                    <span class="font-semibold">Reason you provided:</span>
                    <span class="italic">{{ $pendingRequest->reason }}</span>
                </p>
            @endif

            <form
                method="POST"
                action="{{ route('profile.deletion-request.cancel') }}"
                onsubmit="return confirm('Cancel your account-deletion request?');"
            >
                @csrf
                <button
                    type="submit"
                    class="inline-flex items-center px-3 py-1.5 bg-white border border-amber-300 rounded-md font-semibold text-xs text-amber-900 uppercase tracking-widest hover:bg-amber-50 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition"
                >
                    Cancel request
                </button>
            </form>
        </div>
    @else
        {{-- No pending request — show the form. --}}
        <form
            method="POST"
            action="{{ route('profile.deletion-request.store') }}"
            onsubmit="return confirm('Submit account-deletion request? A superadmin will review and confirm by email.');"
            class="space-y-4"
        >
            @csrf

            <div>
                <label for="deletion-reason" class="block text-sm font-medium text-gray-700">
                    Reason <span class="text-xs text-gray-400">(optional)</span>
                </label>
                <textarea
                    id="deletion-reason"
                    name="reason"
                    rows="3"
                    maxlength="1000"
                    placeholder="e.g. I'm leaving the company, please remove my account."
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                >{{ old('reason') }}</textarea>
                <p class="mt-1 text-xs text-gray-500">
                    Helps the superadmin verify the request. We never share this with anyone else.
                </p>
            </div>

            <button
                type="submit"
                class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition"
            >
                Request Account Deletion
            </button>
        </form>
    @endif
</section>
