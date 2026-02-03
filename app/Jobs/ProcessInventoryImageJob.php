<?php

namespace App\Jobs;

use App\Models\InventoryImage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ProcessInventoryImageJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public InventoryImage $inventoryImage)
    {
        //
    }

    public function handle(): void
    {
        try {
            // Update status to processing
            $this->inventoryImage->update(['processing_status' => InventoryImage::STATUS_PROCESSING]);

            $manager = new ImageManager(new Driver());
            $path = $this->inventoryImage->path;

            if (!Storage::disk('public')->exists($path)) {
                \Illuminate\Support\Facades\Log::warning("Image processing failed: File not found at path '{$path}' for InventoryImage ID {$this->inventoryImage->id}");
                $this->inventoryImage->update(['processing_status' => InventoryImage::STATUS_FAILED]);
                return;
            }

            $imageContent = Storage::disk('public')->get($path);
            $image = $manager->read($imageContent);

            $sizes = [];
            $variants = [
                'thumbnail' => [150, 150],
                'medium' => [600, 600],
                'large' => [1200, 1200],
            ];

            $originalPath = $path;
            $sizes['original'] = Storage::url($originalPath);

            foreach ($variants as $name => $dimensions) {
                $variant = clone $image;
                $variant->scaleDown(width: $dimensions[0], height: $dimensions[1]);

                $filename = pathinfo($path, PATHINFO_FILENAME);
                $extension = pathinfo($path, PATHINFO_EXTENSION);
                $dirname = pathinfo($path, PATHINFO_DIRNAME);

                $variantPath = "{$dirname}/{$filename}_{$name}.{$extension}";

                Storage::disk('public')->put($variantPath, (string) $variant->encode());
                $sizes[$name] = Storage::url($variantPath);
            }

            $this->inventoryImage->update([
                'sizes' => $sizes,
                'processing_status' => InventoryImage::STATUS_COMPLETED,
            ]);
        } catch (\Exception $e) {
            $this->inventoryImage->update(['processing_status' => InventoryImage::STATUS_FAILED]);
            throw $e;
        }
    }
}
