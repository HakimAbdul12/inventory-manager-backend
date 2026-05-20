<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationReceived implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public Notification $notification;
    public array $userIds;

    public function __construct(Notification $notification, array $userIds)
    {
        $this->notification = $notification;
        $this->userIds = $userIds;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array
     */
    public function broadcastOn(): array
    {
        return collect($this->userIds)->map(function ($userId) {
            return new PrivateChannel('user.' . $userId . '.notifications');
        })->toArray();
    }

    /**
     * Get the name the event should broadcast as.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'notification.received';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        // Refresh sender relation to make sure it's loaded
        if (!$this->notification->relationLoaded('sender')) {
            $this->notification->load('sender');
        }

        return [
            'id' => $this->notification->id,
            'title' => $this->notification->title,
            'body' => $this->notification->body,
            'category' => $this->notification->category,
            'action_url' => $this->notification->action_url,
            'created_at' => $this->notification->created_at->toIso8601String(),
            'sender' => $this->notification->sender ? [
                'id' => $this->notification->sender->id,
                'name' => $this->notification->sender->name,
                'avatar' => $this->notification->sender->avatar,
            ] : null,
            'data' => $this->notification->data,
        ];
    }
}
