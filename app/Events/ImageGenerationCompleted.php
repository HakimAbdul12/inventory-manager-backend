<?php

namespace App\Events;

use App\Models\ProcessStep;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ImageGenerationCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ProcessStep $step;
    public array $images;

    public function __construct(ProcessStep $step, array $images = [])
    {
        $this->step = $step;
        $this->images = $images;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel($this->step->process->getBroadcastChannelName()),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ImageGenerationCompleted';
    }

    public function broadcastWith(): array
    {
        return [
            'processId' => $this->step->process_id,
            'stepName' => $this->step->step_name,
            'status' => 'completed',
            'imageCount' => count($this->images),
            'images' => $this->images,
            'duration' => $this->step->duration,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
