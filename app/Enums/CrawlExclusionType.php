<?php

namespace App\Enums;

enum CrawlExclusionType: string
{
    case Exact = 'exact';
    case Contains = 'contains';
    case Regex = 'regex';
}
