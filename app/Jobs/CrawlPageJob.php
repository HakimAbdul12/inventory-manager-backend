<?php

namespace App\Jobs;

use App\Enums\CrawlJobStatus;
use App\Enums\CrawlPageStatus;
use App\Events\CrawlerCompleted;
use App\Events\CrawlerProgressUpdated;
use App\Events\LinkDiscovered;
use App\Events\PageDiscovered;
use App\Events\PageFailed;
use App\Events\PageProcessed;
use App\Models\CrawlJob;
use App\Models\CrawlLink;
use App\Models\CrawlPage;
use App\Services\Crawler\CrawlService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CrawlPageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 60;

    public function __construct(
        public string $crawlJobId,
        public string $pageId,
    ) {}

    public function handle(CrawlService $crawlService): void
    {
        $job = CrawlJob::withoutGlobalScopes()->find($this->crawlJobId);
        $page = CrawlPage::withoutGlobalScopes()->find($this->pageId);

        if (!$job || !$page) {
            return;
        }

        // Check if job was paused or cancelled
        if ($job->isPaused() || $job->isCancelled() || $job->isFinished()) {
            return;
        }

        // Check safety limits
        if ($job->max_pages && $job->pages_discovered >= $job->max_pages) {
            $this->completeJob($job);
            return;
        }

        // Mark page as crawling
        $page->update(['status' => CrawlPageStatus::Crawling]);

        try {
            $response = retry(3, function () use ($page, $job) {
                return Http::timeout(45)
                    ->connectTimeout(15)
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                        'Accept-Language' => 'en-US,en;q=0.9',
                        'Accept-Encoding' => 'gzip, deflate',
                        'Connection' => 'keep-alive',
                        'Upgrade-Insecure-Requests' => '1',
                        'Sec-Fetch-Dest' => 'document',
                        'Sec-Fetch-Mode' => 'navigate',
                        'Sec-Fetch-Site' => 'none',
                        'Sec-Fetch-User' => '?1',
                        'DNT' => '1',
                        'Referer' => $job->seed_url,
                    ])
                    ->get($page->url);
            }, 2000);

            $page->update(['http_status' => $response->status()]);

            if (!$response->successful()) {
                $page->update(['status' => CrawlPageStatus::Failed]);
                broadcast(new PageFailed($job, $page, "HTTP {$response->status()}"));
                $this->broadcastProgress($job);
                $this->checkCompletion($job);
                return;
            }

            $html = $response->body();
            $contentType = $response->header('Content-Type');

            // Only process HTML pages
            if ($contentType && !str_contains($contentType, 'text/html')) {
                $page->update(['status' => CrawlPageStatus::Failed]);
                broadcast(new PageFailed($job, $page, "Non-HTML Content Type: {$contentType}"));
                $this->checkCompletion($job);
                return;
            }

            // Extract content
            $content = $crawlService->extractContent($html);
            $cleanText = $content['clean_text'];
            $wordCount = str_word_count($cleanText);

            $page->update([
                'status' => CrawlPageStatus::Processed,
                'title' => $content['title'] ? mb_substr($content['title'], 0, 512) : null,
                'meta_description' => $content['meta_description'],
                'clean_text' => $cleanText,
                'content_hash' => hash('sha256', $cleanText),
                'word_count' => $wordCount,
            ]);

            // Broadcast page processed
            broadcast(new PageProcessed($job, $page, 0));

            // Extract and process links
            $links = $crawlService->extractLinks($html, $page->url);
            $exclusions = $job->exclusions;
            $newLinksCount = 0;

            foreach ($links as $linkData) {
                $linkUrl = $linkData['url'];

                if (!$crawlService->isInternalUrl($linkUrl, $job->seed_url)) {
                    continue;
                }

                $normalizedUrl = $crawlService->normalizeUrl($linkUrl);

                if ($crawlService->matchesExclusion($normalizedUrl, $exclusions)) {
                    continue;
                }

                $newDepth = $page->depth + 1;
                if ($job->max_depth && $newDepth > $job->max_depth) {
                    continue;
                }

                $existingPage = CrawlPage::where('crawl_job_id', $job->id)
                    ->where('normalized_url', $normalizedUrl)
                    ->first();

                if ($existingPage) {
                    CrawlLink::create([
                        'crawl_job_id' => $job->id,
                        'from_page_id' => $page->id,
                        'to_page_id' => $existingPage->id,
                        'anchor_text' => $linkData['anchor_text'],
                    ]);
                    broadcast(new LinkDiscovered($job->id, $page->id, $existingPage->id, $linkData['anchor_text']));
                    continue;
                }

                $newPage = CrawlPage::create([
                    'crawl_job_id' => $job->id,
                    'url' => $linkUrl,
                    'normalized_url' => $normalizedUrl,
                    'status' => CrawlPageStatus::Queued,
                    'depth' => $newDepth,
                ]);

                CrawlLink::create([
                    'crawl_job_id' => $job->id,
                    'from_page_id' => $page->id,
                    'to_page_id' => $newPage->id,
                    'anchor_text' => $linkData['anchor_text'],
                ]);

                broadcast(new PageDiscovered($job, $newPage));
                broadcast(new LinkDiscovered($job->id, $page->id, $newPage->id, $linkData['anchor_text']));

                $newLinksCount++;

                $freshJob = CrawlJob::withoutGlobalScopes()->find($job->id);
                if ($freshJob && $freshJob->isRunning()) {
                    CrawlPageJob::dispatch($job->id, $newPage->id);
                }
            }

            $this->broadcastProgress($job);

        } catch (\Exception $e) {
            $page->update(['status' => CrawlPageStatus::Failed]);
            broadcast(new PageFailed($job, $page, $e->getMessage()));
            Log::warning("Crawl page failed: {$page->url}", ['error' => $e->getMessage()]);
            $this->broadcastProgress($job);
        }

        $this->checkCompletion($job);
    }

    private function broadcastProgress(CrawlJob $job): void
    {
        $job = CrawlJob::withoutGlobalScopes()->find($job->id);
        if (!$job) return;

        $job->refreshCounters();
        broadcast(new CrawlerProgressUpdated($job));
    }

    private function checkCompletion(CrawlJob $job): void
    {
        $job = CrawlJob::withoutGlobalScopes()->find($job->id);

        if (!$job || !$job->isRunning()) {
            return;
        }

        $pendingCount = CrawlPage::where('crawl_job_id', $job->id)
            ->whereIn('status', [
                CrawlPageStatus::Discovered,
                CrawlPageStatus::Queued,
                CrawlPageStatus::Crawling,
            ])
            ->count();

        if ($pendingCount === 0) {
            $this->completeJob($job);
        }
    }

    private function completeJob(CrawlJob $job): void
    {
        $job = CrawlJob::withoutGlobalScopes()->find($job->id);
        if (!$job || !$job->isRunning()) return;

        Log::info("Crawler [{$job->id}] marking as COMPLETED");

        $job->refreshCounters();
        $job->update([
            'status' => CrawlJobStatus::Completed,
            'completed_at' => now(),
        ]);
        broadcast(new CrawlerCompleted($job));
    }
}
