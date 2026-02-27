<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        // if the message already has a conversation_id we always broadcast there
        $channels = [];

        if ($this->message->conversation_id) {
            $channels[] = new Channel('chat.' . $this->message->conversation_id);
        } else {
            // legacy: broadcast to receiver-specific channel
            $channels[] = new Channel('chat.user.' . $this->message->receiver_id);
            // also broadcast to computed conversation for future compatibility
            $sorted = collect([$this->message->sender_id, $this->message->receiver_id])->sort()->values();
            $channels[] = new Channel('chat.conversation.' . $sorted[0] . '.' . $sorted[1]);
        }

        return $channels;
    }

    public function broadcastWith()
    {
        return ['message' => $this->message->load('sender')];
    }
}
