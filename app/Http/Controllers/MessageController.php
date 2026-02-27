<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
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

        $message = Message::create(array_merge($data, [
            'sender_id' => $me->id,
        ]));

        broadcast(new MessageSent($message))->toOthers();

        return response()->json($message->load('sender'), 201);
    }
}
