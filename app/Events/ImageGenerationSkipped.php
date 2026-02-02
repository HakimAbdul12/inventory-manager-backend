<?php

namespace App\Events;

use App\Models\ProcessStep;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ImageGenerationSkipped implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ProcessStep $step;
    public string $reason;

    public function __construct(ProcessStep $step, string $reason = 'User opted out of image generation')
    {
        $this->step = $step;
        $this->reason = $reason;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel($this->step->process->getBroadcastChannelName()),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ImageGenerationSkipped';
    }

    public function broadcastWith(): array
    {
        return [
            'processId' => $this->step->process_id,
            'stepName' => $this->step->step_name,
            'status' => 'skipped',
            'reason' => $this->reason,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
