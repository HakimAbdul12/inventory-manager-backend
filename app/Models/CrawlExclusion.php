<?php

namespace App\Models;

use App\Enums\CrawlExclusionType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrawlExclusion extends Model
{
    use HasUuids;

    protected $fillable = [
        'crawl_job_id',
        'pattern',
        'type',
    ];

    protected $casts = [
        'type' => CrawlExclusionType::class,
    ];

    protected $attributes = [
        'type' => 'contains',
    ];

    public function crawlJob(): BelongsTo
    {
        return $this->belongsTo(CrawlJob::class);
    }

    /**
     * Check if a URL matches this exclusion pattern.
     */
    public function matches(string $url): bool
    {
        return match ($this->type) {
            CrawlExclusionType::Exact => $url === $this->pattern,
            CrawlExclusionType::Contains => str_contains($url, $this->pattern),
            CrawlExclusionType::Regex => (bool) @preg_match($this->pattern, $url),
        };
    }
}
