<?php

namespace App\Jobs;

use App\Models\CrawlJob;
use App\Services\Crawler\CrawlService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessCrawlContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public function __construct(
        public string $crawlJobId,
    ) {}

    public function handle(CrawlService $crawlService): void
    {
        $job = CrawlJob::withoutGlobalScope('tenant')->find($this->crawlJobId);
        if (!$job) {
            return;
        }

        $crawlService->chunkAndIndex($job);
    }
}
