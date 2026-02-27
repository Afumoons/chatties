<?php

use App\Events\MessageDelivered;
use App\Events\MessageRead;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use function Pest\Laravel\actingAs;

it('marks messages as delivered when requested', function () {
    Event::fake();

    $sender = User::factory()->create();
    $receiver = User::factory()->create();

    $message = Message::create([
        'sender_id' => $sender->id,
        'receiver_id' => $receiver->id,
        'body' => 'Hello',
        'conversation_id' => "conversation.{$sender->id}.{$receiver->id}",
    ]);

    actingAs($receiver)
        ->patchJson(route('messages.delivered', $message->id))
        ->assertStatus(200)
        ->assertJsonFragment(['delivered_at' => $message->fresh()->delivered_at]);

    Event::assertDispatched(MessageDelivered::class);
});

it('marks messages as read when requested', function () {
    Event::fake();

    $sender = User::factory()->create();
    $receiver = User::factory()->create();

    $message = Message::create([
        'sender_id' => $sender->id,
        'receiver_id' => $receiver->id,
        'body' => 'Hello',
        'conversation_id' => "conversation.{$sender->id}.{$receiver->id}",
    ]);

    actingAs($receiver)
        ->patchJson(route('messages.read', $message->id))
        ->assertStatus(200)
        ->assertJsonFragment(['read_at' => $message->fresh()->read_at]);

    Event::assertDispatched(MessageRead::class);
});

it('prevents non-receiver from marking message as delivered', function () {
    $sender = User::factory()->create();
    $receiver = User::factory()->create();
    $other = User::factory()->create();

    $message = Message::create([
        'sender_id' => $sender->id,
        'receiver_id' => $receiver->id,
        'body' => 'Hello',
        'conversation_id' => "conversation.{$sender->id}.{$receiver->id}",
    ]);

    actingAs($other)
        ->patchJson(route('messages.delivered', $message->id))
        ->assertStatus(403);
});
