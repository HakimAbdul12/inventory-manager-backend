<?php

namespace App\Http\Controllers\Api;

use App\Enums\CrawlJobStatus;
use App\Enums\CrawlPageStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCrawlJobRequest;
use App\Jobs\ProcessCrawlContentJob;
use App\Models\CrawlExclusion;
use App\Models\CrawlJob;
use App\Models\CrawlPage;
use App\Models\RagDocument;
use App\Services\Crawler\CrawlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrawlJobController extends Controller
{
    public function __construct(
        private CrawlService $crawlService,
    ) {}

    /**
     * List all crawl jobs for the current tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $jobs = CrawlJob::orderByDesc('created_at')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $jobs->items(),
            'pagination' => [
                'currentPage' => $jobs->currentPage(),
                'lastPage' => $jobs->lastPage(),
                'perPage' => $jobs->perPage(),
                'total' => $jobs->total(),
            ],
        ]);
    }

    /**
     * Create and start a new crawl job.
     */
    public function store(StoreCrawlJobRequest $request): JsonResponse
    {
        $job = CrawlJob::create([
            'seed_url' => $request->seed_url,
            'max_depth' => $request->max_depth,
            'max_pages' => $request->max_pages,
        ]);

        // Create exclusion patterns
        if ($request->exclusions) {
            foreach ($request->exclusions as $exclusion) {
                CrawlExclusion::create([
                    'crawl_job_id' => $job->id,
                    'pattern' => $exclusion['pattern'],
                    'type' => $exclusion['type'] ?? 'contains',
                ]);
            }
        }

        // Start crawling
        $this->crawlService->startCrawl($job);

        $job->load('exclusions');

        return response()->json([
            'success' => true,
            'data' => $job,
            'message' => 'Crawl job created and started.',
        ], 201);
    }

    /**
     * Get crawl job detail with pages, links, and exclusions.
     */
    public function show(string $id): JsonResponse
    {
        $job = CrawlJob::with(['exclusions'])->findOrFail($id);

        $pages = $job->pages()
            ->select(['id', 'crawl_job_id', 'url', 'normalized_url', 'status', 'depth', 'title', 'word_count', 'include_in_rag', 'http_status', 'created_at'])
            ->orderBy('depth')
            ->orderBy('created_at')
            ->get();

        $links = $job->links()
            ->select(['id', 'crawl_job_id', 'from_page_id', 'to_page_id', 'anchor_text'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'job' => $job,
                'pages' => $pages,
                'links' => $links,
            ],
        ]);
    }

    /**
     * Pause a running crawl job.
     */
    public function pause(string $id): JsonResponse
    {
        $job = CrawlJob::findOrFail($id);

        if (!$job->isRunning()) {
            return response()->json([
                'success' => false,
                'message' => 'Job is not currently running.',
            ], 422);
        }

        $job->update(['status' => CrawlJobStatus::Paused]);

        return response()->json([
            'success' => true,
            'data' => $job->fresh(),
            'message' => 'Crawl paused.',
        ]);
    }

    /**
     * Resume a paused crawl job.
     */
    public function resume(string $id): JsonResponse
    {
        $job = CrawlJob::findOrFail($id);

        if (!$job->isPaused()) {
            return response()->json([
                'success' => false,
                'message' => 'Job is not currently paused.',
            ], 422);
        }

        $this->crawlService->resumeCrawl($job);

        return response()->json([
            'success' => true,
            'data' => $job->fresh(),
            'message' => 'Crawl resumed.',
        ]);
    }

    /**
     * Cancel a crawl job.
     */
    public function cancel(string $id): JsonResponse
    {
        $job = CrawlJob::findOrFail($id);

        if ($job->isFinished()) {
            return response()->json([
                'success' => false,
                'message' => 'Job is already finished.',
            ], 422);
        }

        $job->update([
            'status' => CrawlJobStatus::Cancelled,
            'completed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $job->fresh(),
            'message' => 'Crawl cancelled.',
        ]);
    }

    /**
     * Get paginated pages for a crawl job.
     */
    public function pages(Request $request, string $id): JsonResponse
    {
        $job = CrawlJob::findOrFail($id);

        $pages = $job->pages()
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->orderBy('depth')
            ->orderBy('created_at')
            ->paginate($request->get('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $pages->items(),
            'pagination' => [
                'currentPage' => $pages->currentPage(),
                'lastPage' => $pages->lastPage(),
                'perPage' => $pages->perPage(),
                'total' => $pages->total(),
            ],
        ]);
    }

    /**
     * Toggle a page's RAG inclusion.
     */
    public function togglePage(string $id, string $pageId): JsonResponse
    {
        $job = CrawlJob::findOrFail($id);
        $page = $job->pages()->findOrFail($pageId);

        $page->update(['include_in_rag' => !$page->include_in_rag]);

        return response()->json([
            'success' => true,
            'data' => $page->fresh(),
            'message' => $page->include_in_rag ? 'Page included in RAG.' : 'Page excluded from RAG.',
        ]);
    }

    /**
     * Trigger content chunking and RAG indexing.
     */
    public function chunkAndIndex(string $id): JsonResponse
    {
        $job = CrawlJob::findOrFail($id);

        if (!in_array($job->status, [CrawlJobStatus::Completed, CrawlJobStatus::ProcessingContent])) {
            return response()->json([
                'success' => false,
                'message' => 'Crawl must be completed before chunking.',
            ], 422);
        }

        ProcessCrawlContentJob::dispatch($job->id);

        return response()->json([
            'success' => true,
            'message' => 'Content chunking started.',
        ]);
    }

    /**
     * Get page content detail (for preview).
     */
    public function pageContent(string $id, string $pageId): JsonResponse
    {
        $job = CrawlJob::findOrFail($id);
        $page = $job->pages()->findOrFail($pageId);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $page->id,
                'url' => $page->url,
                'title' => $page->title,
                'meta_description' => $page->meta_description,
                'clean_text' => $page->clean_text,
                'word_count' => $page->word_count,
                'status' => $page->status->value,
            ],
        ]);
    }

    /**
     * Delete a crawl job and all related data.
     */
    public function destroy(string $id): JsonResponse
    {
        $job = CrawlJob::findOrFail($id);
        $job->delete(); // cascade deletes handle related records

        return response()->json([
            'success' => true,
            'message' => 'Crawl job deleted.',
        ]);
    }
}
