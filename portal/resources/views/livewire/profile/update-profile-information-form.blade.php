<?php

use App\Models\User;
use App\Models\Machine;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $account_name = '';
    public string $branch = '';

    // Equipment management
    public bool $showMachineForm = false;
    public ?int $editingMachineId = null;
    public string $machine_brand = '';
    public string $machine_model = '';
    public string $machine_serial_number = '';
    public string $machine_nickname = '';
    public bool $machine_is_primary = false;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->account_name = $user->account_name ?? '';
        $this->branch = $user->branch ?? '';
    }

    /**
     * Get the user's machines.
     */
    public function getMachinesProperty()
    {
        return Machine::where('user_id', Auth::id())
            ->orderBy('is_primary', 'desc')
            ->orderBy('brand')
            ->get();
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'account_name' => ['nullable', 'string', 'max:255'],
            'branch' => ['nullable', 'string', 'max:255'],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function sendVerification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    /**
     * Open the machine form for creating a new machine.
     */
    public function createMachine(): void
    {
        $this->editingMachineId = null;
        $this->machine_brand = '';
        $this->machine_model = '';
        $this->machine_serial_number = '';
        $this->machine_nickname = '';
        $this->machine_is_primary = $this->machines->isEmpty();
        $this->showMachineForm = true;
    }

    /**
     * Open the machine form for editing an existing machine.
     */
    public function editMachine(int $machineId): void
    {
        $machine = Machine::where('user_id', Auth::id())->findOrFail($machineId);
        
        $this->editingMachineId = $machine->id;
        $this->machine_brand = $machine->brand ?? '';
        $this->machine_model = $machine->model ?? '';
        $this->machine_serial_number = $machine->serial_number ?? '';
        $this->machine_nickname = $machine->nickname ?? '';
        $this->machine_is_primary = $machine->is_primary;
        $this->showMachineForm = true;
    }

    /**
     * Save the machine (create or update).
     */
    public function saveMachine(): void
    {
        $validated = $this->validate([
            'machine_brand' => ['required', 'string', 'max:120'],
            'machine_model' => ['required', 'string', 'max:120'],
            'machine_serial_number' => ['nullable', 'string', 'max:120'],
            'machine_nickname' => ['nullable', 'string', 'max:120'],
            'machine_is_primary' => ['boolean'],
        ]);

        $userId = Auth::id();

        // If marking as primary, unset primary on all other machines
        if ($validated['machine_is_primary']) {
            Machine::where('user_id', $userId)
                ->when($this->editingMachineId, fn($q) => $q->where('id', '!=', $this->editingMachineId))
                ->update(['is_primary' => false]);
        }

        if ($this->editingMachineId) {
            // Update existing machine
            $machine = Machine::where('user_id', $userId)->findOrFail($this->editingMachineId);
            $machine->update([
                'brand' => $validated['machine_brand'],
                'model' => $validated['machine_model'],
                'serial_number' => $validated['machine_serial_number'],
                'nickname' => $validated['machine_nickname'],
                'is_primary' => $validated['machine_is_primary'],
            ]);
        } else {
            // Create new machine
            Machine::create([
                'user_id' => $userId,
                'brand' => $validated['machine_brand'],
                'model' => $validated['machine_model'],
                'serial_number' => $validated['machine_serial_number'],
                'nickname' => $validated['machine_nickname'],
                'is_primary' => $validated['machine_is_primary'],
            ]);
        }

        $this->showMachineForm = false;
        $this->editingMachineId = null;
        $this->dispatch('machine-saved');
    }

    /**
     * Delete a machine.
     */
    public function deleteMachine(int $machineId): void
    {
        $machine = Machine::where('user_id', Auth::id())->findOrFail($machineId);
        $machine->delete();
        $this->dispatch('machine-deleted');
    }

    /**
     * Cancel the machine form.
     */
    public function cancelMachineForm(): void
    {
        $this->showMachineForm = false;
        $this->editingMachineId = null;
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form wire:submit="updateProfileInformation" class="mt-6 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <x-input-label for="name" :value="__('Name')" />
                <x-text-input wire:model="name" id="name" name="name" type="text" class="mt-1 block w-full" required autofocus autocomplete="name" />
                <x-input-error class="mt-2" :messages="$errors->get('name')" />
            </div>

            <div>
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input wire:model="email" id="email" name="email" type="email" class="mt-1 block w-full" required autocomplete="username" />
                <x-input-error class="mt-2" :messages="$errors->get('email')" />
            </div>

            <div>
                <x-input-label for="account_name" :value="__('Account Name')" />
                <x-text-input wire:model="account_name" id="account_name" name="account_name" type="text" class="mt-1 block w-full" />
                <x-input-error class="mt-2" :messages="$errors->get('account_name')" />
            </div>

            <div>
                <x-input-label for="branch" :value="__('Branch')" />
                <x-text-input wire:model="branch" id="branch" name="branch" type="text" class="mt-1 block w-full" />
                <x-input-error class="mt-2" :messages="$errors->get('branch')" />
            </div>
        </div>

        @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
            <div>
                <p class="text-sm mt-2 text-gray-800">
                    {{ __('Your email address is unverified.') }}

                    <button wire:click.prevent="sendVerification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        {{ __('Click here to re-send the verification email.') }}
                    </button>
                </p>

                @if (session('status') === 'verification-link-sent')
                    <p class="mt-2 font-medium text-sm text-green-600">
                        {{ __('A new verification link has been sent to your email address.') }}
                    </p>
                @endif
            </div>
        @endif

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            <x-action-message class="me-3" on="profile-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>
</section>

{{-- Equipment Management Section --}}
<section class="mt-12">
    <header class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-medium text-gray-900">
                {{ __('Equipment') }}
            </h2>
            <p class="mt-1 text-sm text-gray-600">
                {{ __('Manage your equipment. Registered equipment will appear as options when creating service tickets.') }}
            </p>
        </div>
        
        @if (!$showMachineForm)
            <button wire:click="createMachine" class="inline-flex items-center px-3 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                + Add Equipment
            </button>
        @endif
    </header>

    {{-- Machine Form --}}
    @if ($showMachineForm)
        <div class="mt-6 p-6 bg-gray-50 border border-gray-200 rounded-lg">
            <h3 class="text-md font-medium text-gray-900 mb-4">
                {{ $editingMachineId ? __('Edit Equipment') : __('Add New Equipment') }}
            </h3>
            
            <form wire:submit="saveMachine" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="machine_brand" :value="__('Brand *')" />
                        <x-text-input wire:model="machine_brand" id="machine_brand" type="text" class="mt-1 block w-full" required />
                        <x-input-error class="mt-2" :messages="$errors->get('machine_brand')" />
                    </div>

                    <div>
                        <x-input-label for="machine_model" :value="__('Model *')" />
                        <x-text-input wire:model="machine_model" id="machine_model" type="text" class="mt-1 block w-full" required />
                        <x-input-error class="mt-2" :messages="$errors->get('machine_model')" />
                    </div>

                    <div>
                        <x-input-label for="machine_serial_number" :value="__('Serial Number')" />
                        <x-text-input wire:model="machine_serial_number" id="machine_serial_number" type="text" class="mt-1 block w-full" />
                        <x-input-error class="mt-2" :messages="$errors->get('machine_serial_number')" />
                    </div>

                    <div>
                        <x-input-label for="machine_nickname" :value="__('Nickname')" />
                        <x-text-input wire:model="machine_nickname" id="machine_nickname" type="text" class="mt-1 block w-full" placeholder="e.g. Lab Hematology Analyzer" />
                        <x-input-error class="mt-2" :messages="$errors->get('machine_nickname')" />
                    </div>
                </div>

                <div class="flex items-center">
                    <input wire:model="machine_is_primary" id="machine_is_primary" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                    <label for="machine_is_primary" class="ml-2 text-sm text-gray-700">
                        {{ __('Set as primary equipment') }}
                    </label>
                </div>

                <div class="flex items-center gap-3 pt-4">
                    <x-primary-button type="submit">
                        {{ $editingMachineId ? __('Update') : __('Add') }}
                    </x-primary-button>
                    <button wire:click="cancelMachineForm" type="button" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                        {{ __('Cancel') }}
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- Equipment List --}}
    <div class="mt-6 space-y-3">
        @forelse ($this->machines as $machine)
            <div class="p-4 bg-white border border-gray-200 rounded-lg hover:shadow-sm transition">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <h3 class="font-medium text-gray-900">{{ $machine->brand }} {{ $machine->model }}</h3>
                            @if ($machine->is_primary)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-green-100 text-green-800">
                                    Primary
                                </span>
                            @endif
                        </div>
                        
                        <div class="mt-1 space-y-1 text-sm text-gray-600">
                            @if ($machine->serial_number)
                                <div>S/N: {{ $machine->serial_number }}</div>
                            @endif
                            @if ($machine->nickname)
                                <div>{{ $machine->nickname }}</div>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-2 ml-4">
                        <button wire:click="editMachine({{ $machine->id }})" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                            Edit
                        </button>
                        <button wire:click="deleteMachine({{ $machine->id }})" 
                                wire:confirm="Are you sure you want to delete this equipment?"
                                class="text-red-600 hover:text-red-900 text-sm font-medium">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-8 bg-gray-50 border border-gray-200 rounded-lg">
                <p class="text-gray-500 text-sm">No equipment registered yet.</p>
                <p class="text-gray-400 text-xs mt-1">Add your first equipment to speed up ticket creation.</p>
            </div>
        @endforelse
    </div>

    <x-action-message class="mt-3" on="machine-saved">
        {{ __('Equipment saved.') }}
    </x-action-message>
    <x-action-message class="mt-3" on="machine-deleted">
        {{ __('Equipment deleted.') }}
    </x-action-message>
</section>
