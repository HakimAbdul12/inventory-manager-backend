<?php

namespace App\Events;

use App\Models\TestDrive;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewTestDriveBooked implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public TestDrive $testDrive
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('test-drives.' . $this->testDrive->tenant_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'test-drive.booked';
    }

    public function broadcastWith(): array
    {
        return [
            'test_drive' => [
                'id' => $this->testDrive->id,
                'booking_code' => $this->testDrive->booking_code,
                'visitor_name' => $this->testDrive->visitor_name,
                'scheduled_date' => $this->testDrive->scheduled_date?->toDateString(),
                'scheduled_time' => $this->testDrive->scheduled_time,
                'end_time' => $this->testDrive->end_time,
                'status' => $this->testDrive->status,
                'vehicle_id' => $this->testDrive->vehicle_id,
                'created_at' => $this->testDrive->created_at?->toIso8601String(),
            ],
        ];
    }
}
