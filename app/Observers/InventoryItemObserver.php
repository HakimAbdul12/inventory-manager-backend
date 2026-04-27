<?php

namespace App\Observers;

use App\Jobs\GenerateInventoryEmbeddingJob;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\Log;

class InventoryItemObserver
{
    /**
     * Handle the InventoryItem "saving" event.
     * Before save, we check if attributes changed and regenerate vector_string.
     */
    public function saving(InventoryItem $inventoryItem): void
    {
        // Whenever generated_data changes, we want to construct the vector_string
        if ($inventoryItem->isDirty('generated_data') || $inventoryItem->isDirty('status') || empty($inventoryItem->vector_string)) {
            $inventoryItem->vector_string = $this->buildVectorString($inventoryItem);
        }
    }

    /**
     * Handle the InventoryItem "saved" event.
     * After save, if vector_string was changed, we dispatch the embedding generation job.
     */
    public function saved(InventoryItem $inventoryItem): void
    {
        if ($inventoryItem->wasChanged('vector_string') && !empty($inventoryItem->vector_string)) {
            GenerateInventoryEmbeddingJob::dispatch($inventoryItem);
        }
    }

    /**
     * Handle the InventoryItem "created" event.
     * Just in case `wasChanged` didn't catch the initial creation.
     */
    public function created(InventoryItem $inventoryItem): void
    {
        if (!empty($inventoryItem->vector_string)) {
            GenerateInventoryEmbeddingJob::dispatch($inventoryItem);
        }
    }

    /**
     * Build the string used to generate embeddings.
     */
    protected function buildVectorString(InventoryItem $item): string
    {
        $parts = [];
        $data = $item->generated_data ?? [];

        // Dynamic extraction based on Category schema
        if ($item->category && !empty($item->category->fields)) {
            foreach ($item->category->fields as $field) {
                $key = $field['key'];
                // Skip system fields or empty values
                if ($key === 'system_id' || !isset($data[$key]) || $data[$key] === '') {
                    continue;
                }
                
                $label = $field['label'] ?? ucfirst(str_replace('_', ' ', $key));
                $value = $data[$key];
                
                // Format the value based on its type
                if (is_array($value)) {
                    $value = implode(', ', array_filter($value, fn($v) => !is_array($v)));
                } elseif (is_bool($value)) {
                    $value = $value ? 'Yes' : 'No';
                } elseif ($field['type'] === 'number' && stripos($label, 'price') !== false) {
                    $value = "$" . number_format((float)$value, 0);
                }

                if ($value !== '') {
                    $parts[] = "{$label}: {$value}";
                }
            }
        } else {
            // Fallback for items without explicit category fields mapped
            foreach ($data as $key => $value) {
                if (is_scalar($value) && $value !== '') {
                    $label = ucfirst(str_replace('_', ' ', $key));
                    $parts[] = "{$label}: {$value}";
                }
            }
        }
        
        // Add title if present
        if (!empty($item->title)) {
            $parts[] = "Title: " . $item->title;
        }

        return implode(' | ', $parts);
    }
}
