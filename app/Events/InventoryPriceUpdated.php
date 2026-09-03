<?php

namespace App\Events;

use App\Models\InventoryItem;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InventoryPriceUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public InventoryItem $item,
        public ?float $oldPrice,
        public float $newPrice,
        public string $source = 'manual',
        public ?string $userId = null
    ) {}

    public function broadcastOn(): array
    {
        $channels = [
            new Channel('inventory'),
        ];

        if ($this->item->tenant_id) {
            $channels[] = new Channel("tenant.{$this->item->tenant_id}.inventory");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'InventoryPriceUpdated';
    }

    public function broadcastWith(): array
    {
        $data = $this->item->generated_data ?? [];
        $title = $this->item->title;
        if (empty($title) && !empty($data['make'])) {
            $title = trim(($data['year'] ?? '') . ' ' . ($data['make'] ?? '') . ' ' . ($data['model'] ?? ''));
        }

        return [
            'id' => (string) $this->item->id,
            'tenant_id' => $this->item->tenant_id ? (string) $this->item->tenant_id : null,
            'title' => $title ?: 'Inventory Vehicle',
            'old_price' => $this->oldPrice !== null ? (float) $this->oldPrice : null,
            'new_price' => (float) $this->newPrice,
            'source' => $this->source,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
