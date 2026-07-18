<?php

use App\Models\Machine;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

/**
 * Customer-facing machine manager.
 *
 * Customers can add, edit, delete, and mark-as-primary the machines
 * they have on file. The new-ticket form (`/tickets/new`) reads from
 * the same table to pre-fill brand/model/serial.
 *
 * Only one machine per user can be the primary; setting a new one
 * clears the previous primary in the same transaction.
 */
new class extends Component
{
    // List/edit state ----------------------------------------------------
    public ?int $editingId = null;

    // Form fields --------------------------------------------------------
    public string $nickname         = '';
    public string $brand            = '';
    public string $model            = '';
    public string $serial_number    = '';
    public string $installation_date = '';
    public string $notes            = '';
    public bool   $is_primary       = false;

    // Delete confirmation ------------------------------------------------
    public ?int $confirmingDeleteId = null;

    public function mount(): void
    {
        // No-op: form is empty until the user clicks "Add machine".
    }

    /**
     * All machines belonging to the current user, fresh on every render.
     * The User::machines() relation already orders primary-first.
     */
    #[Computed]
    public function machines()
    {
        return Auth::user()->machines()->get();
    }

    public function startAdd(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->dispatch('machine-form-opened');
    }

    public function startEdit(int $id): void
    {
        $machine = $this->findOwned($id);
        $this->editingId         = $machine->id;
        $this->nickname          = (string) ($machine->nickname ?? '');
        $this->brand             = (string) $machine->brand;
        $this->model             = (string) $machine->model;
        $this->serial_number     = (string) ($machine->serial_number ?? '');
        $this->installation_date = optional($machine->installation_date)->format('Y-m-d') ?? '';
        $this->notes             = (string) ($machine->notes ?? '');
        $this->is_primary        = (bool) $machine->is_primary;
        $this->dispatch('machine-form-opened');
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
        $this->editingId = null;
    }

    public function save(): void
    {
        $userId = Auth::id();

        $data = $this->validate([
            'nickname'          => ['nullable', 'string', 'max:80'],
            'brand'             => ['required', 'string', 'max:120'],
            'model'             => ['required', 'string', 'max:120'],
            'serial_number'     => ['nullable', 'string', 'max:120'],
            'installation_date' => ['nullable', 'date'],
            'notes'             => ['nullable', 'string', 'max:1000'],
            'is_primary'        => ['boolean'],
        ]);

        // Strip empty optional fields so DB stays clean.
        foreach (['nickname', 'serial_number', 'installation_date', 'notes'] as $f) {
            if (($data[$f] ?? null) === '' || $data[$f] === null) {
                unset($data[$f]);
            }
        }
        $data['is_primary'] = (bool) ($data['is_primary'] ?? false);
        $data['user_id']    = $userId;

        $isNew = $this->editingId === null;

        if ($isNew) {
            $machine = Machine::create($data);
        } else {
            $machine = $this->findOwned($this->editingId);
            $machine->update($data);
        }

        // Handle "mark as primary" outside the form: clear any other
        // primary row for this user, then flip this one on.
        if ($data['is_primary']) {
            Machine::where('user_id', $userId)
                ->where('id', '!=', $machine->id)
                ->update(['is_primary' => false]);
        } elseif ($isNew && Machine::where('user_id', $userId)->count() === 1) {
            // First machine ever — auto-promote to primary.
            $machine->update(['is_primary' => true]);
        }

        session()->flash('machine-status', $isNew ? 'Machine added.' : 'Machine updated.');
        $this->resetForm();
        $this->editingId = null;
        unset($this->machines); // refresh computed
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmingDeleteId = $id;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    public function deleteMachine(): void
    {
        if ($this->confirmingDeleteId === null) {
            return;
        }
        $machine = $this->findOwned($this->confirmingDeleteId);
        $wasPrimary = $machine->is_primary;
        $machine->delete();

        // If we deleted the primary, promote the most recently
        // updated remaining machine (or none if list is empty).
        if ($wasPrimary) {
            $replacement = Auth::user()->machines()->latest('updated_at')->first();
            if ($replacement) {
                $replacement->update(['is_primary' => true]);
            }
        }

        $this->confirmingDeleteId = null;
        session()->flash('machine-status', 'Machine removed.');
        unset($this->machines);
    }

    public function makePrimary(int $id): void
    {
        $machine = $this->findOwned($id);
        Machine::where('user_id', Auth::id())
            ->where('id', '!=', $machine->id)
            ->update(['is_primary' => false]);
        $machine->update(['is_primary' => true]);
        session()->flash('machine-status', 'Primary machine updated.');
        unset($this->machines);
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * Find a machine that belongs to the current user, or abort 404.
     * Prevents a customer from editing/deleting another customer's row
     * by tampering with the Livewire payload.
     */
    protected function findOwned(int $id): Machine
    {
        return Machine::where('user_id', Auth::id())->findOrFail($id);
    }

    protected function resetForm(): void
    {
        $this->nickname          = '';
        $this->brand             = '';
        $this->model             = '';
        $this->serial_number     = '';
        $this->installation_date = '';
        $this->notes             = '';
        $this->is_primary        = false;
    }
}; ?>

<section class="space-y-6">
    <x-ui.card
        title="My machines"
        subtitle="Save the equipment you want service for. When you open a new ticket you'll pick from this list — brand, model, and serial number will be filled in for you."
        padding="p-6"
    >
        <x-slot:icon>
            <span aria-hidden="true" class="w-7 h-7 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
            </span>
        </x-slot:icon>

        @if (session('machine-status'))
            <x-ui.toast type="success" title="Saved!">
                {{ session('machine-status') }}
            </x-ui.toast>
        @endif

        {{-- ─── Existing machines list ─── --}}
        <div class="space-y-3">
            @forelse ($this->machines as $machine)
                <div wire:key="machine-{{ $machine->id }}"
                     class="rounded-xl border border-base-300/70 bg-base-100 px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-medium text-base-content truncate">
                                {{ $machine->nickname ?: $machine->brand.' '.$machine->model }}
                            </span>
                            @if ($machine->is_primary)
                                <span class="badge badge-info badge-sm font-medium">
                                    Primary
                                </span>
                            @endif
                        </div>
                        <div class="mt-1 text-sm text-base-content/70">
                            {{ $machine->brand }} · {{ $machine->model }}
                            @if ($machine->serial_number)
                                <span class="text-base-content/30 mx-1">·</span> S/N {{ $machine->serial_number }}
                            @endif
                            @if ($machine->installation_date)
                                <span class="text-base-content/30 mx-1">·</span> installed {{ $machine->installation_date->format('M Y') }}
                            @endif
                        </div>
                        @if ($machine->notes)
                            <div class="mt-1 text-xs text-base-content/60 line-clamp-2">{{ $machine->notes }}</div>
                        @endif
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        @if (! $machine->is_primary)
                            <button type="button" wire:click="makePrimary({{ $machine->id }})"
                                    class="text-xs font-medium text-base-content/70 hover:text-base-content underline">
                                Make primary
                            </button>
                        @endif
                        <button type="button" wire:click="startEdit({{ $machine->id }})"
                                class="btn btn-ghost btn-sm">
                            Edit
                        </button>
                        <button type="button" wire:click="confirmDelete({{ $machine->id }})"
                                class="btn btn-outline btn-error btn-sm">
                            Remove
                        </button>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-base-300 bg-base-200/40 px-4 py-6 text-center">
                    <p class="text-sm text-base-content/70">No machines saved yet.</p>
                    <p class="mt-1 text-xs text-base-content/50">Add your first machine below — you can save as many as you need.</p>
                </div>
            @endforelse
        </div>

        {{-- ─── Delete confirmation ─── --}}
        @if ($confirmingDeleteId !== null)
            <div wire:key="delete-confirm" class="rounded-xl border border-error/30 bg-error/5 px-4 py-3 flex items-center justify-between gap-3">
                <span class="text-sm text-base-content/80">Remove this machine from your list?</span>
                <div class="flex items-center gap-2">
                    <button type="button" wire:click="cancelDelete"
                            class="btn btn-ghost btn-sm">
                        Cancel
                    </button>
                    <button type="button" wire:click="deleteMachine"
                            class="btn btn-error btn-sm">
                        Yes, remove
                    </button>
                </div>
            </div>
        @endif

        {{-- ─── Add / Edit form ─── --}}
        <div class="rounded-xl border border-base-300/70 bg-base-200/50 px-4 py-4 mt-4">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-base-content">
                    {{ $editingId ? 'Edit machine' : 'Add a machine' }}
                </h3>
                @if ($editingId)
                    <button type="button" wire:click="cancelEdit" class="btn btn-ghost btn-xs">
                        Cancel
                    </button>
                @endif
            </div>

            <form wire:submit="save" class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <x-input-label for="machine-nickname" value="Nickname (optional)" />
                    <x-text-input wire:model="nickname" id="machine-nickname" type="text" maxlength="80"
                                   class="mt-1 block w-full"
                                   placeholder="e.g. ICU Monitor #2" />
                    <p class="mt-1 text-xs text-base-content/60">A short label so you can tell your machines apart.</p>
                    <x-input-error class="mt-2" :messages="$errors->get('nickname')" />
                </div>

                <div>
                    <x-input-label for="machine-brand" value="Brand *" />
                    <x-text-input wire:model="brand" id="machine-brand" type="text" maxlength="120"
                                   class="mt-1 block w-full" required
                                   placeholder="e.g. Mindray" />
                    <x-input-error class="mt-2" :messages="$errors->get('brand')" />
                </div>

                <div>
                    <x-input-label for="machine-model" value="Model *" />
                    <x-text-input wire:model="model" id="machine-model" type="text" maxlength="120"
                                   class="mt-1 block w-full" required
                                   placeholder="e.g. BC-6800" />
                    <x-input-error class="mt-2" :messages="$errors->get('model')" />
                </div>

                <div>
                    <x-input-label for="machine-serial" value="Serial number" />
                    <x-text-input wire:model="serial_number" id="machine-serial" type="text" maxlength="120"
                                   class="mt-1 block w-full"
                                   placeholder="(optional)" />
                    <x-input-error class="mt-2" :messages="$errors->get('serial_number')" />
                </div>

                <div>
                    <x-input-label for="machine-installation" value="Installation date" />
                    <x-text-input wire:model="installation_date" id="machine-installation" type="date"
                                   class="mt-1 block w-full" />
                    <x-input-error class="mt-2" :messages="$errors->get('installation_date')" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="machine-notes" value="Notes" />
                    <textarea wire:model="notes" id="machine-notes" rows="2" maxlength="1000"
                              class="textarea textarea-bordered mt-1 block w-full text-sm focus:outline-none focus:border-primary"
                              placeholder="Location, accessories, anything the technician should know."></textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                </div>

                <div class="sm:col-span-2 flex items-center justify-between">
                    <label class="inline-flex items-center gap-2 text-sm text-base-content/80 cursor-pointer">
                        <input type="checkbox" wire:model="is_primary"
                               class="checkbox checkbox-secondary checkbox-sm">
                        Mark as primary machine
                    </label>

                    <button type="submit"
                            class="btn btn-primary btn-sm gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ $editingId ? 'Save changes' : 'Add machine' }}
                    </button>
                </div>
            </form>
        </div>
    </x-ui.card>
</section>
