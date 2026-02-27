<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast when a widget conversation state changes (ai→human, human→ai, →closed).
 */
class WidgetStateChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $conversationId,
        public string $previousState,
        public string $newState,
        public ?string $agentName = null,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("chat-conversation.{$this->conversationId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'state.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'previous_state' => $this->previousState,
            'new_state' => $this->newState,
            'agent_name' => $this->agentName,
        ];
    }
}
