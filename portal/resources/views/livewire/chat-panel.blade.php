<?php

use Livewire\Attributes\On;
use Livewire\Volt\Component;

new class extends Component
{
    public int $ticketId;
    public string $currentUserName;
    public string $currentUserRole;

    /**
     * Initial chat history passed in by the parent view as a
     * plain array of associative arrays (see
     * Customer\ChatController::loadMessageHistory). The Alpine
     * wrapper handles new messages from this point on, so the
     * component itself doesn't need to re-query.
     *
     * Typed as `array` (not `Collection`) because Livewire 3 treats
     * `Collection` properties as Eloquent collections, which would
     * fail on items that are plain arrays.
     */
    public array $messages = [];

    public string $body = '';

    public function mount(
        int $ticketId,
        string $currentUserName,
        string $currentUserRole,
        array $messages = [],
    ): void {
        $this->ticketId        = $ticketId;
        $this->currentUserName = $currentUserName;
        $this->currentUserRole = $currentUserRole;
        $this->messages        = $messages;
    }

    public function send(): void
    {
        $body = trim($this->body);
        if ($body === '') {
            return;
        }

        $request = request();
        $request->merge(['body' => $body]);

        $controller = match (true) {
            in_array($this->currentUserRole, ['fse', 'its', 'manager', 'admin'], true)
                => app(\App\Http\Controllers\Tsp\ChatController::class),
            default
                => app(\App\Http\Controllers\Customer\ChatController::class),
        };
        $controller->send($request, (string) $this->ticketId);

        // Skip Livewire's re-render of this component: the controller
        // already persisted the message and broadcast it on Pusher,
        // and the Alpine `chat-sent-ack` handler clears the input. A
        // re-render would also wipe any Alpine-appended rows from the
        // chat log (because the server-side template re-renders
        // from $this->messages, which doesn't include the just-sent
        // message until next page load).
        $this->skipRender();
        $this->dispatch('chat-sent-ack');
    }
}; ?>

<div
    x-data="chatPanel({
        ticketId: @js($ticketId),
        currentUserName: @js($currentUserName),
        currentUserRole: @js($currentUserRole),
    })"
    x-init="init()"
    class="bg-white shadow sm:rounded-lg flex flex-col h-[640px]"
>
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h3 class="text-base font-medium text-gray-900">
            Chat with {{ $currentUserRole === 'customer' ? 'our support team' : 'the customer' }}
        </h3>
        <span class="text-xs text-gray-400" x-show="connecting">Connecting…</span>
        <span class="text-xs text-green-500" x-show="! connecting && connected" x-cloak>● Live</span>
    </div>

    <div
        id="chat-log-{{ $ticketId }}"
        class="flex-1 overflow-y-auto px-6 py-4 space-y-3"
    >
        {{ $slot ?? '' }}
        @isset($messages)
            @forelse ($messages as $msg)
                <div class="flex {{ $msg['mine'] ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[80%] rounded-lg px-4 py-2 text-sm
                                {{ $msg['mine'] ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-900' }}">
                        @if (! $msg['mine'])
                            <div class="text-xs font-semibold mb-1 opacity-70">
                                {{ $msg['sender_name'] }}
                                <span class="ml-1 px-1.5 py-0.5 rounded bg-white/60 text-[10px] uppercase tracking-wider">
                                    {{ $msg['sender_role'] }}
                                </span>
                            </div>
                        @endif
                        <div class="whitespace-pre-wrap break-words">{{ $msg['body'] }}</div>
                        <div class="text-[10px] mt-1 {{ $msg['mine'] ? 'text-indigo-100' : 'text-gray-400' }}">
                            @php
                                $ts = $msg['created_at'] ?? null;
                                echo $ts ? \Carbon\Carbon::parse($ts)->format('M j, g:i A') : '—';
                            @endphp
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center text-sm text-gray-400 mt-12">
                    No messages yet — say hello 👋
                </div>
            @endforelse
        @endisset
    </div>

    <form wire:submit.prevent="send" class="border-t border-gray-200 px-4 py-3 flex gap-2">
        <input
            type="text"
            wire:model="body"
            placeholder="Type a message…"
            maxlength="2000"
            autocomplete="off"
            class="flex-1 rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
        >
        <button
            type="submit"
            wire:loading.attr="disabled"
            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-xs font-semibold uppercase tracking-widest rounded-md hover:bg-indigo-700 disabled:opacity-50"
        >
            Send
        </button>
    </form>
</div>
