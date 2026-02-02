<?php

namespace App\Events;

use App\Models\ProcessStep;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TextGenerationCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ProcessStep $step;
    public array $preview;

    public function __construct(ProcessStep $step, array $preview = [])
    {
        $this->step = $step;
        $this->preview = $preview;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel($this->step->process->getBroadcastChannelName()),
        ];
    }

    public function broadcastAs(): string
    {
        return 'TextGenerationCompleted';
    }

    public function broadcastWith(): array
    {
        return [
            'processId' => $this->step->process_id,
            'stepName' => $this->step->step_name,
            'status' => 'completed',
            'duration' => $this->step->duration,
            'preview' => $this->preview,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
