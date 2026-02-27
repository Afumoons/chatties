<?php

use App\Events\MessageSent;

use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use function Pest\Laravel\actingAs;

it('broadcasts a message when one is created', function () {
    Event::fake();

    $sender = User::factory()->create();
    $receiver = User::factory()->create();

    $response = actingAs($sender)
        ->postJson(route('messages.store'), [
            'receiver_id' => $receiver->id,
            'body' => 'Hello world!',
        ]);

    $response->assertStatus(201);

    $data = $response->json();
    expect($data['body'])->toBe('Hello world!');
    expect($data['sender_id'])->toBe($sender->id);
    expect($data['receiver_id'])->toBe($receiver->id);
    expect($data['conversation_id'])->toBe("conversation.{$sender->id}.{$receiver->id}");

    Event::assertDispatched(MessageSent::class, function ($event) use ($sender, $receiver) {
        return $event->message->sender_id === $sender->id
            && $event->message->receiver_id === $receiver->id
            && $event->message->conversation_id === "conversation.{$sender->id}.{$receiver->id}";
    });
});
