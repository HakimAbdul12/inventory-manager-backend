<?php

namespace App\Services\Publishing;

use App\Contracts\PublishingPlatformService;
use App\Models\PublishingPlatform;
use App\Services\Publishing\Platforms\AutotechPublishingService;
use App\Services\Publishing\Platforms\FacebookAdsPublishingService;
use App\Services\Publishing\Platforms\GenericMarketplaceService;
use App\Services\Publishing\Platforms\GoogleAdsPublishingService;
use App\Services\Publishing\Platforms\OnlyEvPublishingService;
use InvalidArgumentException;

class PublishingManager
{
    /**
     * @var array<string, PublishingPlatformService>
     */
    protected array $services = [];

    public function __construct()
    {
        $this->register(new AutotechPublishingService());
        $this->register(new OnlyEvPublishingService());
        $this->register(new GoogleAdsPublishingService());
        $this->register(new FacebookAdsPublishingService());
    }

    public function register(PublishingPlatformService $service): void
    {
        $this->services[$service->getPlatformKey()] = $service;
    }

    public function getService(string $platformKey): PublishingPlatformService
    {
        if (isset($this->services[$platformKey])) {
            return $this->services[$platformKey];
        }

        // Check if platform exists in database
        $platform = PublishingPlatform::where('key', $platformKey)->first();
        if ($platform) {
            $service = new GenericMarketplaceService($platformKey, $platform->name);
            $this->services[$platformKey] = $service;
            return $service;
        }

        throw new InvalidArgumentException("Publishing platform service not found for '{$platformKey}'.");
    }

    /**
     * @return array<string, PublishingPlatformService>
     */
    public function getAllServices(): array
    {
        return $this->services;
    }
}
