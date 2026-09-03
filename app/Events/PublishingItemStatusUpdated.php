<?php

namespace App\Events;

use App\Models\PublishingBatchItem;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PublishingItemStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public PublishingBatchItem $batchItem,
        public array $extraData = []
    ) {}

    public function broadcastOn(): array
    {
        $channels = [
            new Channel("publishing-batch.{$this->batchItem->batch_id}"),
        ];

        if ($this->batchItem->batch?->tenant_id) {
            $channels[] = new Channel("tenant.{$this->batchItem->batch->tenant_id}.publishing");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'PublishingItemStatusUpdated';
    }

    public function broadcastWith(): array
    {
        $batch = $this->batchItem->batch;
        $item = $this->batchItem->inventoryItem;
        $vehicleTitle = $item?->generated_data['title'] ?? 'Vehicle';

        return [
            'batch_id' => $this->batchItem->batch_id,
            'batch_item_id' => $this->batchItem->id,
            'inventory_item_id' => $this->batchItem->inventory_item_id,
            'vehicle_title' => $vehicleTitle,
            'platform_key' => $this->batchItem->platform_key,
            'format' => $this->batchItem->format,
            'status' => $this->batchItem->status,
            'attempts' => $this->batchItem->attempts,
            'max_attempts' => $this->batchItem->max_attempts,
            'error_message' => $this->batchItem->error_message,
            'last_attempted_at' => $this->batchItem->last_attempted_at?->toIso8601String(),
            'external_url' => $this->extraData['url'] ?? null,
            'message' => $this->extraData['message'] ?? null,
            'batch_progress' => [
                'status' => $batch->status,
                'total' => $batch->total_items,
                'successful' => $batch->successful_items,
                'failed' => $batch->failed_items,
            ],
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
