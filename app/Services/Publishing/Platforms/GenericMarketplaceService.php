<?php

namespace App\Services\Publishing\Platforms;

use App\Contracts\PublishingPlatformService;
use App\Models\InventoryItem;

class GenericMarketplaceService implements PublishingPlatformService
{
    protected string $platformKey;
    protected string $name;

    public function __construct(string $platformKey, string $name)
    {
        $this->platformKey = $platformKey;
        $this->name = $name;
    }

    public function getPlatformKey(): string
    {
        return $this->platformKey;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function publish(InventoryItem $item, array $options = []): array
    {
        $format = $options['format'] ?? 'image';

        return [
            'success' => true,
            'external_id' => $this->platformKey . '-' . substr(md5($item->id), 0, 8),
            'url' => "https://www.{$this->platformKey}.com/listing/" . substr(md5($item->id), 0, 8),
            'message' => "Published successfully to {$this->name} in {$format} format.",
        ];
    }
}
