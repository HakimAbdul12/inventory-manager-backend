<?php

namespace App\Events;

use App\Models\CrawlJob;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CrawlerProgressUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public CrawlJob $crawlJob,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel($this->crawlJob->getBroadcastChannelName()),
        ];
    }

    public function broadcastAs(): string
    {
        return 'CrawlerProgressUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'crawl_job_id' => $this->crawlJob->id,
            'status' => $this->crawlJob->status->value,
            'pages_discovered' => $this->crawlJob->pages_discovered,
            'pages_processed' => $this->crawlJob->pages_processed,
            'pages_excluded' => $this->crawlJob->pages_excluded,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
