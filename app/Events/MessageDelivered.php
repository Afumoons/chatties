<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageDelivered implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        $channels = [];
        if ($this->message->conversation_id) {
            $channels[] = new Channel('chat.' . $this->message->conversation_id);
        }
        return $channels;
    }

    public function broadcastWith()
    {
        return [
            'message_id' => $this->message->id,
            'delivered_at' => $this->message->delivered_at,
        ];
    }
}
