<?php

use Livewire\Volt\Component;

new class extends Component
{
    public int $ticketId;
    public string $currentUserName;
    public string $currentUserRole;

    /**
     * Initial internal-note history passed in by the parent view as
     * a plain array of associative arrays. The Alpine wrapper
     * handles new notes from this point on, so the component itself
     * doesn't need to re-query.
     */
    public array $notes = [];

    public string $body = '';

    public function mount(
        int $ticketId,
        string $currentUserName,
        string $currentUserRole,
        array $notes = [],
    ): void {
        $this->ticketId        = $ticketId;
        $this->currentUserName = $currentUserName;
        $this->currentUserRole = $currentUserRole;
        $this->notes           = $notes;
    }

    public function submit(): void
    {
        $body = trim($this->body);
        if ($body === '') {
            return;
        }

        $request = request();
        $request->merge(['body' => $body]);

        app(\App\Http\Controllers\Tsp\InternalNoteController::class)
            ->store($request, (string) $this->ticketId);

        // Skip render: controller already persisted + broadcast on
        // Pusher. Alpine's `note-sent-ack` listener clears the input.
        $this->skipRender();
        $this->dispatch('note-sent-ack');
    }
}; ?>

<div
    x-data="internalNotesPanel({
        ticketId: @js($ticketId),
        currentUserName: @js($currentUserName),
        currentUserRole: @js($currentUserRole),
    })"
    x-init="init()"
    class="bg-white shadow sm:rounded-lg flex flex-col h-[640px]"
>
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h3 class="text-base font-medium text-gray-900">
            Internal notes
        </h3>
        <span class="flex items-center gap-1.5 text-xs text-gray-500">
            <span
                x-show="pusherConnected"
                x-cloak
                class="inline-block h-2 w-2 rounded-full bg-green-500"
            ></span>
            <span x-show="pusherConnected" x-cloak>Live</span>
        </span>
    </div>
    <p class="px-6 pt-2 text-xs text-amber-700 bg-amber-50 border-b border-amber-100">
        Visible to {{ $currentUserRole === 'admin' ? 'admin' : 'TSP' }} only. Never shown to the customer.
    </p>

    <div
        class="flex-1 overflow-y-auto px-6 py-4 space-y-3"
        x-ref="log"
    >
        <template x-for="note in notes" :key="note.id">
            <article class="rounded-md border border-amber-200 bg-amber-50/40 px-4 py-3">
                <header class="flex items-center justify-between text-xs text-gray-600">
                    <span class="font-medium text-amber-900" x-text="note.author_name"></span>
                    <time
                        :datetime="note.created_at"
                        x-text="formatTime(note.created_at)"
                    ></time>
                </header>
                <p class="mt-1 text-sm text-gray-800 whitespace-pre-wrap break-words" x-text="note.body"></p>
                <footer class="mt-2 text-[10px] uppercase tracking-wide text-amber-700"
                        x-text="note.author_role"></footer>
            </article>
        </template>

        <p
            x-show="notes.length === 0"
            class="text-sm text-gray-500 italic"
        >
            No internal notes yet. Add the first one below — only TSP / admin can see this.
        </p>
    </div>

    <form
        wire:submit.prevent="submit"
        class="px-6 py-4 border-t border-gray-200"
    >
        <label class="sr-only" for="internal-note-body">Add an internal note</label>
        <textarea
            id="internal-note-body"
            x-ref="input"
            wire:model="body"
            rows="3"
            maxlength="5000"
            placeholder="Add an internal note (visible to TSP only)..."
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 text-sm"
        ></textarea>
        <div class="mt-2 flex justify-end">
            <button
                type="submit"
                class="inline-flex items-center px-3 py-1.5 bg-amber-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-700 active:bg-amber-800 focus:outline-none focus:border-amber-800 focus:ring focus:ring-amber-200 transition"
            >
                Add note
            </button>
        </div>
    </form>
</div>
