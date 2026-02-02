<?php

namespace App\Events;

use App\Models\InventoryProcess;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InventoryGenerationStarted implements ShouldBroadcast
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
        return 'InventoryGenerationStarted';
    }

    public function broadcastWith(): array
    {
        return [
            'processId' => $this->process->id,
            'category' => $this->process->category->slug,
            'steps' => $this->process->steps->map(fn($step) => [
                'name' => $step->step_name,
                'order' => $step->step_order,
                'status' => $step->status->value,
            ])->toArray(),
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
