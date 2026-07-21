<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold tracking-widest uppercase text-base-content/50 mb-1">
                Account
            </p>
            <h2 class="font-semibold text-2xl text-base-content leading-tight">
                {{ __('Profile') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-7xl mx-auto sm:px-4 lg:px-6 space-y-6">
            <div class="max-w-xl">
                <livewire:profile.update-profile-information-form />
            </div>

            <div class="max-w-xl">
                <livewire:profile.update-password-form />
            </div>

            @if (auth()->user()->isCustomer())
                {{-- Customer-only: register / edit / remove the
                     equipment they have on file so the new-ticket
                     form can pre-fill brand, model, and serial. --}}
                <div class="max-w-3xl">
                    <livewire:profile.manage-machines-form />
                </div>
            @endif

            <div class="max-w-xl">
                @if (auth()->user()->isSuperAdmin())
                    {{-- Superadmins can self-delete directly. --}}
                    <livewire:profile.delete-user-form />
                @else
                    {{-- Customers / TSPs must file a request that a
                         superadmin reviews. Prevents accidental data
                         loss on the ticket / chat / audit history. --}}
                    @include('profile.partials.request-deletion-card')
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
