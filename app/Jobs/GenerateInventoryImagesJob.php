<?php

namespace App\Jobs;

use App\Events\ImageGenerationCompleted;
use App\Events\ImageGenerationSkipped;
use App\Models\InventoryProcess;
use App\Services\AIContentService;
use App\Services\InventoryGenerationService;
use App\Services\OpenRouterClient;
use App\Services\ProcessTrackingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateInventoryImagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 180; // Increased for image generation
    public int $backoff = 10;

    protected InventoryProcess $process;

    public function __construct(InventoryProcess $process)
    {
        $this->process = $process;
    }

    public function handle(
        AIContentService $aiService,
        ProcessTrackingService $trackingService,
        InventoryGenerationService $generationService,
        OpenRouterClient $openRouterClient
    ): void {
        $process = $this->process->fresh(['inventoryItem', 'steps']);
        $step = $process->steps()->where('step_name', 'image_generation')->first();

        if (!$step) {
            Log::error('Image generation step not found', ['process_id' => $process->id]);
            return;
        }

        // Double-check if images should be generated
        if (!$process->shouldGenerateImages()) {
            $trackingService->skipStep($step, 'User opted out of image generation');
            broadcast(new ImageGenerationSkipped($step->fresh()))->toOthers();

            FinalizeInventoryProcessJob::dispatch($process)
                ->onQueue(config('inventory.queue.name', 'inventory'));
            return;
        }

        try {
            $trackingService->startStep($step);
            $step->addLog('info', 'Starting AI image generation with Seedream 4.5');

            $inventoryItem = $process->inventoryItem;
            if (!$inventoryItem) {
                throw new \RuntimeException('Inventory item not found');
            }

            $generatedData = $inventoryItem->generated_data ?? [];
            $imageCount = $process->getImageCount();

            // Generate image prompts using AI
            $imagePrompts = $aiService->generateImagePrompts($generatedData, $imageCount);
            $step->addLog('info', "Generated {$imageCount} image prompts");

            $images = [];

            // Generate each image using the Seedream model
            foreach ($imagePrompts as $index => $prompt) {
                try {
                    $step->addLog('info', "Generating image " . ($index + 1) . " of {$imageCount}");

                    // Call OpenRouter with Seedream model
                    $generatedImages = $openRouterClient->generateImages($prompt);

                    if (!empty($generatedImages)) {
                        $imageData = $generatedImages[0]['data']; // base64 data URL

                        // Store the image
                        $imagePath = $this->storeImage($imageData, $process->id, $index);

                        $alt = $this->generateAltText($generatedData, $index);

                        // Create InventoryImage record
                        $inventoryImage = \App\Models\InventoryImage::create([
                            'inventory_item_id' => $inventoryItem->id,
                            'path' => $imagePath,
                            'prompt' => $prompt,
                            'generated_by' => 'seedream-4.5',
                            'alt' => $alt,
                            'is_primary' => $index === 0,
                            'processing_status' => \App\Models\InventoryImage::STATUS_PENDING,
                        ]);

                        $images[] = [
                            'url' => Storage::url($imagePath),
                            'thumbnail' => Storage::url($imagePath),
                            'alt' => $alt,
                            'isPrimary' => $index === 0,
                            'generatedBy' => 'seedream-4.5',
                            'prompt' => $prompt,
                        ];

                        // Dispatch background processing job
                        ProcessInventoryImageJob::dispatch($inventoryImage)
                            ->onQueue(config('inventory.queue.name', 'inventory'));

                        $step->addLog('success', "Image " . ($index + 1) . " generated successfully");
                    }
                } catch (\Exception $e) {
                    $step->addLog('warning', "Failed to generate image " . ($index + 1) . ": " . $e->getMessage());
                    Log::warning('Individual image generation failed', [
                        'process_id' => $process->id,
                        'index' => $index,
                        'error' => $e->getMessage(),
                    ]);
                    // Continue with other images
                }
            }

            if (empty($images)) {
                throw new \RuntimeException('No images were generated successfully');
            }

            // Add images to inventory item
            // Images are now stored in relation, no need to update JSON column
            // But we keep $images array for the broadcast event
            // foreach ($images as $image) {
            //     $inventoryItem->addImage($image);
            // }

            $trackingService->completeStep($step, ['image_count' => count($images)]);

            // Broadcast completion
            broadcast(new ImageGenerationCompleted($step->fresh(), $images))->toOthers();

            Log::info('Image generation completed', [
                'process_id' => $process->id,
                'image_count' => count($images),
            ]);

            // Dispatch finalization job
            FinalizeInventoryProcessJob::dispatch($process)
                ->onQueue(config('inventory.queue.name', 'inventory'));
        } catch (\Exception $e) {
            Log::error('Image generation failed', [
                'process_id' => $process->id,
                'error' => $e->getMessage(),
            ]);

            $trackingService->failStep($step, $e->getMessage());

            if ($this->attempts() >= $this->tries) {
                // Image generation is optional, so we still proceed to finalization
                // but mark images as failed
                $step->addLog('warning', 'Proceeding without images due to generation failure');

                FinalizeInventoryProcessJob::dispatch($process)
                    ->onQueue(config('inventory.queue.name', 'inventory'));
            } else {
                throw $e;
            }
        }
    }

    /**
     * Store a base64 image to disk storage.
     */
    protected function storeImage(string $base64Data, string $processId, int $index): ?string
    {
        try {
            // Check if it's a data URL
            if (str_starts_with($base64Data, 'data:image/')) {
                // Extract the base64 part
                $parts = explode(',', $base64Data, 2);
                if (count($parts) !== 2) {
                    return null;
                }

                // Get the mime type
                preg_match('/data:image\/(\w+);/', $parts[0], $mimeMatch);
                $extension = $mimeMatch[1] ?? 'jpg';
                if ($extension === 'jpeg') {
                    $extension = 'jpg';
                }

                // Decode the base64 data
                $imageContent = base64_decode($parts[1]);

                // Generate a unique filename
                $filename = "inventory/{$processId}/image_{$index}_{$this->generateUniqueId()}.{$extension}";

                // Store the file
                Storage::disk('public')->put($filename, $imageContent);

                return $filename;
            }

            return null;
        } catch (\Exception $e) {
            Log::warning('Failed to store image', [
                'process_id' => $processId,
                'index' => $index,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Generate alt text for an image based on inventory data.
     */
    protected function generateAltText(array $generatedData, int $index): string
    {
        $make = $generatedData['make'] ?? 'Vehicle';
        $model = $generatedData['model'] ?? '';
        $year = $generatedData['year'] ?? date('Y');
        $color = $generatedData['color'] ?? '';

        $angles = ['Front View', 'Side View', 'Rear View', 'Interior', 'Detail'];
        $angle = $angles[$index] ?? "View " . ($index + 1);

        $parts = array_filter([$year, $make, $model, $color, $angle]);
        return implode(' ', $parts);
    }

    /**
     * Generate a unique ID for file naming.
     */
    protected function generateUniqueId(): string
    {
        return substr(md5(uniqid((string) mt_rand(), true)), 0, 8);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Image generation job failed permanently', [
            'process_id' => $this->process->id,
            'error' => $exception->getMessage(),
        ]);

        // Still proceed to finalization without images
        FinalizeInventoryProcessJob::dispatch($this->process)
            ->onQueue(config('inventory.queue.name', 'inventory'));
    }
}
