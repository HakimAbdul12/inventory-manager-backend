<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ImportInventoryJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    protected $import;

    public function __construct(\App\Models\Import $import)
    {
        $this->import = $import;
    }

    public function handle(): void
    {
        $this->import->update(['status' => 'processing']);

        $path = storage_path('app/' . $this->import->file_path);

        if (!file_exists($path)) {
            $this->import->update(['status' => 'failed', 'errors' => ['File not found']]);
            return;
        }

        $mappings = $this->import->mappings;
        $categorySlug = $this->import->category_slug;
        $processed = 0;
        $errors = [];

        if (($handle = fopen($path, 'r')) !== false) {
            $header = fgetcsv($handle);

            while (($row = fgetcsv($handle)) !== false) {
                try {
                    // Combine header with row data
                    if (count($header) !== count($row)) {
                        continue; // Skip malformed rows
                    }

                    $rowData = array_combine($header, $row);
                    $attributes = [];

                    // Transform using mappings
                    foreach ($mappings as $csvHeader => $dbField) {
                        if (isset($rowData[$csvHeader]) && $dbField) {
                            $attributes[$dbField] = $rowData[$csvHeader];
                        }
                    }

                    // Create Inventory Item
                    // Assuming we have an InventoryItem model or similar. 
                    // Based on previous context, items might be "InventoryItem" or just "Item"
                    // And they belong to a category. 

                    // We need to look up the category ID first
                    $category = \App\Models\Category::where('slug', $categorySlug)->first();

                    if ($category) {
                        \App\Models\InventoryItem::create([
                            'category_id' => $category->id,
                            'name' => $attributes['make'] . ' ' . $attributes['model'] ?? 'Unknown Item',
                            'data' => $attributes, // JSON column for flexible fields
                            'status' => 'draft', // or active
                        ]);
                    }

                    $processed++;

                    // Update progress every 10 rows
                    if ($processed % 10 === 0) {
                        $this->import->update(['processed_rows' => $processed]);
                    }
                } catch (\Exception $e) {
                    $errors[] = "Row $processed: " . $e->getMessage();
                }
            }
            fclose($handle);
        }

        $this->import->update([
            'status' => empty($errors) ? 'completed' : 'completed_with_errors',
            'processed_rows' => $processed,
            'errors' => $errors
        ]);
    }
}
