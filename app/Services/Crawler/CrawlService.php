<?php

namespace App\Services\Crawler;

use App\Enums\CrawlExclusionType;
use App\Enums\CrawlJobStatus;
use App\Enums\CrawlPageStatus;
use App\Jobs\CrawlPageJob;
use App\Models\CrawlExclusion;
use App\Models\CrawlJob;
use App\Models\CrawlPage;
use App\Models\RagDocument;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CrawlService
{
    /**
     * Start a crawl job: seed the first URL into the queue.
     */
    public function startCrawl(CrawlJob $job): void
    {
        $job->update([
            'status' => CrawlJobStatus::Running,
            'started_at' => now(),
        ]);

        $normalizedSeed = $this->normalizeUrl($job->seed_url);

        $page = CrawlPage::create([
            'crawl_job_id' => $job->id,
            'url' => $job->seed_url,
            'normalized_url' => $normalizedSeed,
            'status' => CrawlPageStatus::Queued,
            'depth' => 0,
        ]);

        CrawlPageJob::dispatch($job->id, $page->id);
    }

    /**
     * Resume a paused crawl by re-dispatching queued/discovered pages.
     */
    public function resumeCrawl(CrawlJob $job): void
    {
        $job->update(['status' => CrawlJobStatus::Running]);

        $pendingPages = $job->pages()
            ->whereIn('status', [
                CrawlPageStatus::Discovered->value,
                CrawlPageStatus::Queued->value,
            ])
            ->get();

        foreach ($pendingPages as $page) {
            $page->update(['status' => CrawlPageStatus::Queued]);
            CrawlPageJob::dispatch($job->id, $page->id);
        }
    }

    // ─── URL Normalization ──────────────────────────────────

    /**
     * Normalize a URL: strip anchors, trailing slashes, UTM params, sort query params.
     */
    public function normalizeUrl(string $url): string
    {
        $parsed = parse_url($url);
        if (!$parsed || !isset($parsed['host'])) {
            return $url;
        }

        $scheme = strtolower($parsed['scheme'] ?? 'https');
        $host = strtolower($parsed['host']);
        $port = $parsed['port'] ?? null;
        $path = $parsed['path'] ?? '/';

        // Remove trailing slash (but keep root /)
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        // Parse and clean query params
        $query = '';
        if (!empty($parsed['query'])) {
            parse_str($parsed['query'], $params);

            // Remove tracking params
            $trackingParams = [
                'utm_source', 'utm_medium', 'utm_campaign',
                'utm_term', 'utm_content', 'fbclid', 'gclid',
                'ref', 'source',
            ];
            foreach ($trackingParams as $param) {
                unset($params[$param]);
            }

            if (!empty($params)) {
                ksort($params);
                $query = '?' . http_build_query($params);
            }
        }

        $portStr = '';
        if ($port && !(($scheme === 'http' && $port == 80) || ($scheme === 'https' && $port == 443))) {
            $portStr = ':' . $port;
        }

        return "{$scheme}://{$host}{$portStr}{$path}{$query}";
    }

    // ─── Internal Link Detection ────────────────────────────

    /**
     * Check if a URL is an internal link relative to the seed domain.
     */
    public function isInternalUrl(string $url, string $seedUrl): bool
    {
        // Reject non-HTTP protocols
        $lowerUrl = strtolower($url);
        if (Str::startsWith($lowerUrl, ['mailto:', 'tel:', 'javascript:', 'data:', 'ftp:', '#'])) {
            return false;
        }

        $seedParsed = parse_url($seedUrl);
        $urlParsed = parse_url($url);

        if (!$seedParsed || !$urlParsed) {
            return false;
        }

        $seedHost = strtolower($seedParsed['host'] ?? '');
        $urlHost = strtolower($urlParsed['host'] ?? '');

        if (empty($urlHost)) {
            return false;
        }

        return $seedHost === $urlHost;
    }

    /**
     * Resolve a potentially relative URL against a base URL.
     */
    public function resolveUrl(string $relativeUrl, string $baseUrl): string
    {
        // Already absolute
        if (Str::startsWith($relativeUrl, ['http://', 'https://'])) {
            return $relativeUrl;
        }

        // Protocol-relative
        if (Str::startsWith($relativeUrl, '//')) {
            $scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?? 'https';
            return "{$scheme}:{$relativeUrl}";
        }

        $parsed = parse_url($baseUrl);
        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? '';
        $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';

        // Fragment-only or empty
        if (empty($relativeUrl) || $relativeUrl[0] === '#') {
            return $baseUrl;
        }

        // Root-relative
        if ($relativeUrl[0] === '/') {
            return "{$scheme}://{$host}{$port}{$relativeUrl}";
        }

        // Relative path
        $basePath = $parsed['path'] ?? '/';
        $baseDir = substr($basePath, 0, (int) strrpos($basePath, '/') + 1);
        return "{$scheme}://{$host}{$port}{$baseDir}{$relativeUrl}";
    }

    // ─── Exclusion Matching ─────────────────────────────────

    /**
     * Check if a URL matches any exclusion pattern.
     */
    public function matchesExclusion(string $url, Collection $exclusions): bool
    {
        foreach ($exclusions as $exclusion) {
            if ($exclusion->matches($url)) {
                return true;
            }
        }
        return false;
    }

    // ─── Content Extraction ─────────────────────────────────

    /**
     * Extract clean text content from HTML.
     * Removes nav, footer, script, style, header, and aside elements.
     *
     * @return array{title: ?string, meta_description: ?string, clean_text: string}
     */
    public function extractContent(string $html): array
    {
        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();

        $xpath = new \DOMXPath($doc);

        // Extract title
        $titleNodes = $xpath->query('//title');
        $title = $titleNodes && $titleNodes->length > 0
            ? trim($titleNodes->item(0)->textContent)
            : null;

        // Extract meta description
        $metaDesc = null;
        $metaNodes = $xpath->query('//meta[@name="description"]/@content');
        if ($metaNodes && $metaNodes->length > 0) {
            $metaDesc = trim($metaNodes->item(0)->textContent);
        }

        // Remove unwanted elements
        $removeSelectors = ['//nav', '//footer', '//script', '//style', '//header', '//aside', '//noscript', '//iframe'];
        foreach ($removeSelectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes) {
                foreach ($nodes as $node) {
                    $node->parentNode->removeChild($node);
                }
            }
        }

        // Extract body text
        $bodyNodes = $xpath->query('//body');
        $bodyText = '';
        if ($bodyNodes && $bodyNodes->length > 0) {
            $bodyText = $bodyNodes->item(0)->textContent;
        }

        // Clean whitespace
        $cleanText = preg_replace('/\s+/', ' ', $bodyText);
        $cleanText = trim($cleanText);

        return [
            'title' => $title,
            'meta_description' => $metaDesc,
            'clean_text' => $cleanText,
        ];
    }

    /**
     * Extract all href links from HTML.
     *
     * @return array{url: string, anchor_text: string}[]
     */
    public function extractLinks(string $html, string $baseUrl): array
    {
        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();

        $links = [];
        $anchors = $doc->getElementsByTagName('a');

        foreach ($anchors as $anchor) {
            $href = $anchor->getAttribute('href');
            if (empty($href)) {
                continue;
            }

            $resolvedUrl = $this->resolveUrl($href, $baseUrl);
            $anchorText = trim($anchor->textContent);

            $links[] = [
                'url' => $resolvedUrl,
                'anchor_text' => Str::limit($anchorText, 500),
            ];
        }

        return $links;
    }

    // ─── Content Chunking for RAG ───────────────────────────

    /**
     * Split text into chunks of approximately $maxTokens tokens with overlap.
     * Using a rough estimate of 1 token ≈ 4 characters.
     *
     * @return string[]
     */
    public function chunkContent(string $text, int $maxTokens = 600, int $overlapTokens = 100): array
    {
        $maxChars = $maxTokens * 4;
        $overlapChars = $overlapTokens * 4;

        if (mb_strlen($text) <= $maxChars) {
            return [$text];
        }

        $chunks = [];
        $offset = 0;
        $textLength = mb_strlen($text);

        while ($offset < $textLength) {
            $chunk = mb_substr($text, $offset, $maxChars);

            // Try to break at a sentence boundary
            if ($offset + $maxChars < $textLength) {
                $lastPeriod = mb_strrpos($chunk, '. ');
                $lastNewline = mb_strrpos($chunk, "\n");
                $breakPoint = max($lastPeriod ?: 0, $lastNewline ?: 0);

                if ($breakPoint > $maxChars * 0.5) {
                    $chunk = mb_substr($chunk, 0, $breakPoint + 1);
                }
            }

            $chunks[] = trim($chunk);
            $offset += mb_strlen($chunk) - $overlapChars;

            if ($offset < 0) {
                $offset = 0;
            }
        }

        return array_filter($chunks, fn($c) => !empty(trim($c)));
    }

    /**
     * Create RAG document chunks for a completed crawl job.
     */
    public function chunkAndIndex(CrawlJob $job): void
    {
        $job->update(['status' => CrawlJobStatus::ProcessingContent]);

        // Remove existing RAG documents for this job
        RagDocument::where('crawl_job_id', $job->id)->delete();

        $pages = $job->pages()
            ->withoutGlobalScopes()
            ->where('status', CrawlPageStatus::Processed->value)
            ->where('include_in_rag', true)
            ->whereNotNull('clean_text')
            ->get();

        foreach ($pages as $page) {
            $chunks = $this->chunkContent($page->clean_text);

            foreach ($chunks as $index => $chunkText) {
                RagDocument::create([
                    'crawl_job_id' => $job->id,
                    'page_id' => $page->id,
                    'content' => $chunkText,
                    'chunk_index' => $index,
                    'metadata_json' => [
                        'url' => $page->url,
                        'title' => $page->title,
                        'crawl_job_id' => $job->id,
                        'chunk_index' => $index,
                    ],
                ]);
            }
        }

        $job->update(['status' => CrawlJobStatus::Completed]);
    }
}
