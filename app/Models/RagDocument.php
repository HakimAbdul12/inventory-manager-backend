<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RagDocument extends Model
{
    use HasUuids;

    protected $fillable = [
        'crawl_job_id',
        'page_id',
        'content',
        'chunk_index',
        'metadata_json',
        'vectorized',
    ];

    protected $casts = [
        'chunk_index' => 'integer',
        'metadata_json' => 'array',
        'vectorized' => 'boolean',
    ];

    protected $attributes = [
        'chunk_index' => 0,
        'vectorized' => false,
    ];

    public function crawlJob(): BelongsTo
    {
        return $this->belongsTo(CrawlJob::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(CrawlPage::class, 'page_id');
    }
}
