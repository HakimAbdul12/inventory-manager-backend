<?php

namespace App\Events;

use App\Models\ProcessStep;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StepStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ProcessStep $step;

    public function __construct(ProcessStep $step)
    {
        $this->step = $step;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel($this->step->process->getBroadcastChannelName()),
        ];
    }

    public function broadcastAs(): string
    {
        return 'StepStatusUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'processId' => $this->step->process_id,
            'stepName' => $this->step->step_name,
            'status' => $this->step->status->value,
            'startedAt' => $this->step->started_at?->toIso8601String(),
            'completedAt' => $this->step->completed_at?->toIso8601String(),
            'duration' => $this->step->duration,
            'logs' => $this->step->logs ?? [],
            'error' => $this->step->error,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
