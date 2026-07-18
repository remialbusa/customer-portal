@php
    /** @var \App\Models\User $user */
    $user = auth()->user();
    $pendingRequest = \App\Models\AccountDeletionRequest::latestPendingFor($user->id);
@endphp

<section class="space-y-6">
    <x-ui.card
        title="Delete account"
        subtitle="Account deletion is handled by our superadmin team to keep your ticket history, chat logs, and audit trail safe. Submit a request below and they will review it and confirm by email."
        padding="p-6"
    >
        <x-slot:icon>
            <span aria-hidden="true" class="w-7 h-7 rounded-lg bg-error/10 text-error flex items-center justify-center shrink-0">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/></svg>
            </span>
        </x-slot:icon>

        @if (session('status'))
            <div role="alert" class="alert alert-success shadow-sm flex-col items-start gap-1 p-3 text-sm">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div role="alert" class="alert alert-error shadow-sm flex-col items-start gap-1 p-3 text-sm">
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($pendingRequest)
            {{-- A request is already in flight. Show its status and
                 give the user a way to cancel if they filed by mistake. --}}
            <div role="alert" class="alert alert-warning shadow-sm flex-col items-start gap-2 p-4 text-sm">
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
                        class="btn btn-warning btn-sm"
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
                    <label for="deletion-reason" class="block text-sm font-medium text-base-content/80">
                        Reason <span class="text-xs text-base-content/50 font-normal">(optional)</span>
                    </label>
                    <textarea
                        id="deletion-reason"
                        name="reason"
                        rows="3"
                        maxlength="1000"
                        placeholder="e.g. I'm leaving the company, please remove my account."
                        class="textarea textarea-bordered mt-1 block w-full text-sm focus:outline-none focus:border-primary"
                    >{{ old('reason') }}</textarea>
                    <p class="mt-1 text-xs text-base-content/60">
                        Helps the superadmin verify the request. We never share this with anyone else.
                    </p>
                </div>

                <button
                    type="submit"
                    class="btn btn-error btn-sm gap-1"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/></svg>
                    Request account deletion
                </button>
            </form>
        @endif
    </x-ui.card>
</section>
