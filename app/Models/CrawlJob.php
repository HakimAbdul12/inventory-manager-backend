<?php

namespace App\Models;

use App\Enums\CrawlJobStatus;
use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrawlJob extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'seed_url',
        'status',
        'max_depth',
        'max_pages',
        'pages_discovered',
        'pages_processed',
        'pages_excluded',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'status' => CrawlJobStatus::class,
        'max_depth' => 'integer',
        'max_pages' => 'integer',
        'pages_discovered' => 'integer',
        'pages_processed' => 'integer',
        'pages_excluded' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'scheduled',
        'pages_discovered' => 0,
        'pages_processed' => 0,
        'pages_excluded' => 0,
    ];

    // ─── Relationships ──────────────────────────────────────

    public function pages(): HasMany
    {
        return $this->hasMany(CrawlPage::class);
    }

    public function links(): HasMany
    {
        return $this->hasMany(CrawlLink::class);
    }

    public function exclusions(): HasMany
    {
        return $this->hasMany(CrawlExclusion::class);
    }

    public function ragDocuments(): HasMany
    {
        return $this->hasMany(RagDocument::class);
    }

    // ─── Helpers ────────────────────────────────────────────

    public function getBroadcastChannelName(): string
    {
        return "crawl-job.{$this->id}";
    }

    public function isRunning(): bool
    {
        return $this->status === CrawlJobStatus::Running;
    }

    public function isPaused(): bool
    {
        return $this->status === CrawlJobStatus::Paused;
    }

    public function isCancelled(): bool
    {
        return $this->status === CrawlJobStatus::Cancelled;
    }

    public function isFinished(): bool
    {
        return in_array($this->status, [
            CrawlJobStatus::Completed,
            CrawlJobStatus::Failed,
            CrawlJobStatus::Cancelled,
        ]);
    }

    /**
     * Refresh aggregate counters from page records.
     */
    public function refreshCounters(): void
    {
        // Use withoutGlobalScopes to ensure we see all rows in worker/background context
        $this->update([
            'pages_discovered' => $this->pages()->withoutGlobalScopes()->count(),
            'pages_processed' => $this->pages()->withoutGlobalScopes()->where('status', 'processed')->count(),
            'pages_excluded' => $this->pages()->withoutGlobalScopes()->where('status', 'excluded')->count(),
        ]);
    }
}
