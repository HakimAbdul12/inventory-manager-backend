<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrawlLink extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'crawl_job_id',
        'from_page_id',
        'to_page_id',
        'anchor_text',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $link) {
            if (empty($link->created_at)) {
                $link->created_at = now();
            }
        });
    }

    public function crawlJob(): BelongsTo
    {
        return $this->belongsTo(CrawlJob::class);
    }

    public function fromPage(): BelongsTo
    {
        return $this->belongsTo(CrawlPage::class, 'from_page_id');
    }

    public function toPage(): BelongsTo
    {
        return $this->belongsTo(CrawlPage::class, 'to_page_id');
    }
}
