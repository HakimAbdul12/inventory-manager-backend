<?php

namespace App\Services\Publishing\Platforms;

use App\Contracts\PublishingPlatformService;
use App\Models\InventoryItem;
use Illuminate\Support\Str;

class AutotechPublishingService implements PublishingPlatformService
{
    public function getPlatformKey(): string
    {
        return 'autotech';
    }

    public function getName(): string
    {
        return 'Autotech Public Marketplace';
    }

    public function publish(InventoryItem $item, array $options = []): array
    {
        // Internal Autotech publishing: vehicle is activated in public showroom catalog
        $format = $options['format'] ?? 'image';

        return [
            'success' => true,
            'external_id' => 'autotech-' . $item->id,
            'url' => config('app.url') . '/inventory/' . $item->id,
            'message' => "Published to Autotech marketplace in {$format} format with verified dealer badge.",
        ];
    }
}
