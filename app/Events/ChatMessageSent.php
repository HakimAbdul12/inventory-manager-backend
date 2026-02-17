<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ChatMessage $message)
    {
        $this->message->load('sender:id,name,avatar');
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->message->chat_room_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'chat_room_id' => $this->message->chat_room_id,
                'sender_id' => $this->message->sender_id,
                'sender' => [
                    'id' => $this->message->sender->id,
                    'name' => $this->message->sender->name,
                    'avatar' => $this->message->sender->avatar,
                ],
                'body' => $this->message->body,
                'type' => $this->message->type,
                'metadata' => $this->message->metadata,
                'reply_to_id' => $this->message->reply_to_id,
                'reply_to' => $this->message->reply_to ? [
                    'id' => $this->message->reply_to->id,
                    'body' => $this->message->reply_to->body,
                    'sender_id' => $this->message->reply_to->sender_id,
                    'sender' => $this->message->reply_to->sender ? [
                        'id' => $this->message->reply_to->sender->id,
                        'name' => $this->message->reply_to->sender->name,
                    ] : null,
                ] : null,
                'is_pinned' => $this->message->is_pinned ?? false,
                'created_at' => $this->message->created_at->toIso8601String(),
            ],
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}
