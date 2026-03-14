<?php

namespace App\Models;

use App\Enums\CrawlPageStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrawlPage extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'crawl_job_id',
        'url',
        'normalized_url',
        'status',
        'http_status',
        'depth',
        'title',
        'meta_description',
        'clean_text',
        'content_hash',
        'word_count',
        'include_in_rag',
    ];

    protected $casts = [
        'status' => CrawlPageStatus::class,
        'http_status' => 'integer',
        'depth' => 'integer',
        'word_count' => 'integer',
        'include_in_rag' => 'boolean',
    ];

    protected $attributes = [
        'status' => 'discovered',
        'depth' => 0,
        'word_count' => 0,
        'include_in_rag' => true,
    ];

    public function crawlJob(): BelongsTo
    {
        return $this->belongsTo(CrawlJob::class);
    }

    public function outgoingLinks(): HasMany
    {
        return $this->hasMany(CrawlLink::class, 'from_page_id');
    }

    public function incomingLinks(): HasMany
    {
        return $this->hasMany(CrawlLink::class, 'to_page_id');
    }

    public function ragDocuments(): HasMany
    {
        return $this->hasMany(RagDocument::class, 'page_id');
    }
}
