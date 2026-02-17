<?php

namespace App\Events;

use App\Models\DealerConnection;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConnectionAccepted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public DealerConnection $connection,
        public User $accepter
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->connection->sender_id . '.notifications'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'type' => 'connection_accepted',
            'connection_id' => $this->connection->id,
            'accepter' => [
                'id' => $this->accepter->id,
                'name' => $this->accepter->name,
                'company_name' => $this->accepter->company_name,
                'avatar' => $this->accepter->avatar,
            ],
            'created_at' => now()->toIso8601String(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'notification';
    }
}
