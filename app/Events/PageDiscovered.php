<?php

namespace App\Events;

use App\Models\CrawlJob;
use App\Models\CrawlPage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PageDiscovered implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public CrawlJob $crawlJob,
        public CrawlPage $page,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel($this->crawlJob->getBroadcastChannelName()),
        ];
    }

    public function broadcastAs(): string
    {
        return 'PageDiscovered';
    }

    public function broadcastWith(): array
    {
        return [
            'crawl_job_id' => $this->crawlJob->id,
            'page_id' => $this->page->id,
            'url' => $this->page->url,
            'normalized_url' => $this->page->normalized_url,
            'depth' => $this->page->depth,
            'status' => $this->page->status->value,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
