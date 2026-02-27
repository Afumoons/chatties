<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Events\MessageDelivered;
use App\Events\MessageRead;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(User $user, Request $request)
    {
        $me = $request->user();

        $messages = Message::where(function ($q) use ($me, $user) {
            $q->where('sender_id', $me->id)->where('receiver_id', $user->id);
        })->orWhere(function ($q) use ($me, $user) {
            $q->where('sender_id', $user->id)->where('receiver_id', $me->id);
        })->with('sender')->orderBy('created_at', 'desc')->paginate(25);

        return response()->json($messages);
    }

    public function store(Request $request)
    {
        $me = $request->user();

        $data = $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'body' => 'nullable|string|max:5000',
            'conversation_id' => 'nullable|string',
        ]);

        // determine conversation id string for two participants
        $conversationId = $data['conversation_id'] ?? sprintf(
            'conversation.%d.%d',
            min($me->id, $data['receiver_id']),
            max($me->id, $data['receiver_id']),
        );

        $message = Message::create(array_merge($data, [
            'sender_id' => $me->id,
            'conversation_id' => $conversationId,
        ]));

        broadcast(new MessageSent($message))->toOthers();

        return response()->json($message->load('sender'), 201);
    }

    public function markDelivered(Message $message, Request $request)
    {
        $me = $request->user();

        // only the receiver can mark a message as delivered
        if ($message->receiver_id !== $me->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (!$message->delivered_at) {
            $message->update(['delivered_at' => now()]);
            broadcast(new MessageDelivered($message))->toOthers();
        }

        return response()->json(['delivered_at' => $message->delivered_at]);
    }

    public function markRead(Message $message, Request $request)
    {
        $me = $request->user();

        // only the receiver can mark a message as read
        if ($message->receiver_id !== $me->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (!$message->read_at) {
            $message->update(['read_at' => now()]);
            broadcast(new MessageRead($message))->toOthers();
        }

        return response()->json(['read_at' => $message->read_at]);
    }
}
