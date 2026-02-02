<?php

namespace App\Events;

use App\Models\InventoryProcess;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserProcessUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public InventoryProcess $process;
    public string $action;

    /**
     * Create a new event instance.
     *
     * @param InventoryProcess $process
     * @param string $action 'created', 'updated', 'completed', 'failed'
     */
    public function __construct(InventoryProcess $process, string $action = 'updated')
    {
        $this->process = $process;
        $this->action = $action;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel("user.{$this->process->user_id}.processes"),
        ];
    }

    /**
     * Get the event name to broadcast as.
     */
    public function broadcastAs(): string
    {
        return 'process.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'action' => $this->action,
            'process' => [
                'id' => $this->process->id,
                'category' => $this->process->category->name ?? 'Unknown',
                'status' => $this->process->status->value,
                'currentStep' => $this->process->current_step,
                'inventoryItemId' => $this->process->inventory_item_id,
                'createdAt' => $this->process->created_at->toIso8601String(),
            ],
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
