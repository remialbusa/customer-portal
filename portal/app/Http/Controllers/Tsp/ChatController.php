<?php

namespace App\Http\Controllers\Tsp;

use App\Events\MessageSent;
use App\Http\Controllers\Concerns\AssertsTicketAccess;
use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatController extends Controller
{
    use AssertsTicketAccess;

    /**
     * Show the TSP ticket detail (read-only ticket info + customer chat panel).
     * Internal notes + time tracker land in Phase 4 / 5.
     */
    public function show(string $id): View
    {
        $user = auth()->user();
        $item = $this->loadMondayTicket($id);
        $this->authorizeTicketAccess($user, $item);

        $messages = $this->loadMessageHistory($id, $user);

        return view('tsp.ticket-show', [
            'user'     => $user,
            'ticket'   => $item,
            'messages' => $messages,
        ]);
    }

    /**
     * Persist a new TSP chat message and broadcast it. Returns JSON
     * (no redirect) so Livewire doesn't re-render the whole page —
     * see Customer\ChatController::send for the full rationale.
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
     * Load the full chat history for a ticket as a plain array — see
     * Customer\ChatController::loadMessageHistory for the shared
     * shape contract.
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
