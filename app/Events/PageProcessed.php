<?php

namespace App\Events;

use App\Models\CrawlJob;
use App\Models\CrawlPage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PageProcessed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public CrawlJob $crawlJob,
        public CrawlPage $page,
        public int $linksFound = 0,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel($this->crawlJob->getBroadcastChannelName()),
        ];
    }

    public function broadcastAs(): string
    {
        return 'PageProcessed';
    }

    public function broadcastWith(): array
    {
        return [
            'crawl_job_id' => $this->crawlJob->id,
            'page_id' => $this->page->id,
            'url' => $this->page->url,
            'title' => $this->page->title,
            'word_count' => $this->page->word_count,
            'depth' => $this->page->depth,
            'http_status' => $this->page->http_status,
            'links_found' => $this->linksFound,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
