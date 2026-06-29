<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <livewire:profile.update-profile-information-form />
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <livewire:profile.update-password-form />
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
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
    </div>
</x-app-layout>
