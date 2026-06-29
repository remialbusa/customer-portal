<?php

namespace App\Http\Controllers\Customer;

use App\Events\MessageSent;
use App\Http\Controllers\Concerns\AssertsTicketAccess;
use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ChatController extends Controller
{
    use AssertsTicketAccess;

    /**
     * Show the customer ticket detail + chat panel.
     */
    public function show(string $id): View
    {
        $user = auth()->user();
        $item = $this->loadMondayTicket($id);
        $this->authorizeTicketAccess($user, $item);

        $messages = $this->loadMessageHistory($id, $user);

        return view('customer.tickets.show', [
            'user'     => $user,
            'ticket'   => $item,
            'messages' => $messages,
        ]);
    }

    /**
     * Persist a new message and broadcast it on the ticket channel.
     *
     * Returns a JSON 200 instead of a redirect so Livewire doesn't
     * re-render the whole page (the optimistic `chat-sent-ack` plus
     * the Pusher echo handle appending the new bubble in the
     * sender's tab).
     */
    public function send(Request $request, string $id): JsonResponse
    {
        $user = auth()->user();
        $item = $this->loadMondayTicket($id);
        $this->authorizeTicketAccess($user, $item);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $message = ChatMessage::create([
            'monday_ticket_id' => $id,
            'user_id'          => $user->id,
            'sender_role'      => $user->role,
            'body'             => $data['body'],
        ]);

        $message->load('user');
        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'ok'   => true,
            'id'   => (int) $message->id,
            'body' => (string) $message->body,
            'at'   => $message->created_at?->toIso8601String(),
        ]);
    }

    /**
     * Load the full chat history for a ticket as a plain array of
     * associative arrays (with a `mine` flag for the current viewer's
     * own messages) — the chat-panel Livewire component is a typed
     * `array` bag and we want the server-rendered initial state to
     * use the same shape that the Alpine bridge builds dynamically.
     *
     * Returns a plain array (not a Collection) because Livewire 3
     * treats typed-`Collection` properties as Eloquent collections
     * internally, which can fail on items that are plain arrays.
     */
    protected function loadMessageHistory(string $mondayTicketId, User $viewer): array
    {
        return ChatMessage::with('user')
            ->where('monday_ticket_id', $mondayTicketId)
            ->orderBy('created_at')
            ->get()
            ->map(function (ChatMessage $msg) use ($viewer) {
                return [
                    'id'          => (int) $msg->id,
                    'body'        => (string) $msg->body,
                    'sender_role' => (string) $msg->sender_role,
                    'sender_name' => (string) ($msg->user?->name ?? 'Unknown'),
                    'mine'        => (int) $msg->user_id === (int) $viewer->id,
                    'created_at'  => optional($msg->created_at)->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }
}
