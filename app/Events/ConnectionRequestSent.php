<?php

namespace App\Events;

use App\Models\DealerConnection;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConnectionRequestSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public DealerConnection $connection,
        public User $sender
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->connection->receiver_id . '.notifications'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'type' => 'connection_request',
            'connection_id' => $this->connection->id,
            'sender' => [
                'id' => $this->sender->id,
                'name' => $this->sender->name,
                'company_name' => $this->sender->company_name,
                'avatar' => $this->sender->avatar,
            ],
            'message' => $this->connection->message,
            'created_at' => $this->connection->created_at->toIso8601String(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'notification';
    }
}
