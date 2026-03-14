<?php

namespace App\Enums;

enum CrawlPageStatus: string
{
    case Discovered = 'discovered';
    case Queued = 'queued';
    case Crawling = 'crawling';
    case Processed = 'processed';
    case Failed = 'failed';
    case Excluded = 'excluded';
}
