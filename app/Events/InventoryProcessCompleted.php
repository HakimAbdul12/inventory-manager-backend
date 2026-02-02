<?php

namespace App\Events;

use App\Models\InventoryProcess;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InventoryProcessCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public InventoryProcess $process;

    public function __construct(InventoryProcess $process)
    {
        $this->process = $process;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel($this->process->getBroadcastChannelName()),
        ];
    }

    public function broadcastAs(): string
    {
        return 'InventoryProcessCompleted';
    }

    public function broadcastWith(): array
    {
        return [
            'processId' => $this->process->id,
            'inventoryItemId' => $this->process->inventory_item_id,
            'status' => 'completed',
            'totalDuration' => $this->process->total_duration,
            'redirectUrl' => "/inventory/{$this->process->inventory_item_id}",
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
