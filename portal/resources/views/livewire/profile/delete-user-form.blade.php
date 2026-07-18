<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<section class="space-y-6">
    <x-ui.card
        title="Delete account"
        subtitle="Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain."
        padding="p-6"
    >
        <x-slot:icon>
            <span aria-hidden="true" class="w-7 h-7 rounded-lg bg-error/10 text-error flex items-center justify-center shrink-0">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/></svg>
            </span>
        </x-slot:icon>

        <button
            type="button"
            class="btn btn-error btn-sm gap-1"
            x-data=""
            x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
        >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/></svg>
            {{ __('Delete account') }}
        </button>

        <x-modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable>
            <form wire:submit="deleteUser" class="p-6">

                <h2 class="text-lg font-semibold text-base-content">
                    {{ __('Are you sure you want to delete your account?') }}
                </h2>

                <p class="mt-1 text-sm text-base-content/70">
                    {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                </p>

                <div class="mt-6">
                    <x-input-label for="password" value="{{ __('Password') }}" class="sr-only" />

                    <x-text-input
                        wire:model="password"
                        id="password"
                        name="password"
                        type="password"
                        class="mt-1 block w-3/4"
                        placeholder="{{ __('Password') }}"
                    />

                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <x-secondary-button x-on:click="$dispatch('close')">
                        {{ __('Cancel') }}
                    </x-secondary-button>

                    <x-danger-button>
                        {{ __('Delete account') }}
                    </x-danger-button>
                </div>
            </form>
        </x-modal>
    </x-ui.card>
</section>
