<?php

namespace App\Enums;

enum CrawlJobStatus: string
{
    case Scheduled = 'scheduled';
    case Queued = 'queued';
    case Running = 'running';
    case Paused = 'paused';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case ProcessingContent = 'processing_content';
    case Vectorized = 'vectorized';
}
