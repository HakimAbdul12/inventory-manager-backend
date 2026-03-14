<?php

namespace App\Events;

use App\Models\CrawlJob;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LinkDiscovered implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public CrawlJob $crawlJob,
        public string $fromPageId,
        public string $toPageId,
        public ?string $anchorText = null,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel($this->crawlJob->getBroadcastChannelName()),
        ];
    }

    public function broadcastAs(): string
    {
        return 'LinkDiscovered';
    }

    public function broadcastWith(): array
    {
        return [
            'crawl_job_id' => $this->crawlJob->id,
            'from_page_id' => $this->fromPageId,
            'to_page_id' => $this->toPageId,
            'anchor_text' => $this->anchorText,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
