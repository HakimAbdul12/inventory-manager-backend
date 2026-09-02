<?php

namespace App\Services\Mcp\Tools;

use App\Contracts\McpTool;
use App\Models\InventoryItem;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AIContentService;

class AnalyzeInventoryTool implements McpTool
{
    protected AIContentService $aiService;

    public function __construct(AIContentService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function name(): string
    {
        return 'analyze_inventory';
    }

    public function description(): string
    {
        return 'Run AI analysis on an inventory item to assess data completeness, suggest improvements, estimate market value, and identify missing information.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'string',
                    'description' => 'The inventory item UUID to analyze.',
                ],
            ],
            'required' => ['id'],
        ];
    }

    public function requiredPermission(): ?string
    {
        return 'inventory.ai.analyze';
    }

    public function category(): string
    {
        return 'AI';
    }

    public function execute(array $args, User $user, Tenant $tenant): array
    {
        $item = InventoryItem::where('tenant_id', $tenant->id)
            ->where('id', $args['id'])
            ->with(['images', 'documents'])
            ->first();

        if (!$item) {
            return [
                ['type' => 'text', 'text' => json_encode([
                    'success' => false,
                    'error' => "Inventory item not found with ID: {$args['id']}",
                ])],
            ];
        }

        try {
            $data = $item->generated_data ?? [];

            // Completeness analysis
            $requiredFields = ['make', 'model', 'year', 'price', 'mileage', 'exterior_color', 'vin', 'description'];
            $presentFields = array_filter($requiredFields, fn($f) => !empty($data[$f]));
            $missingFields = array_diff($requiredFields, $presentFields);
            $completeness = count($presentFields) / count($requiredFields) * 100;

            // Media analysis
            $imageCount = $item->images->count();
            $hasDocuments = $item->documents->count() > 0;

            $analysis = [
                'id' => $item->id,
                'title' => $data['title'] ?? ($data['year'] ?? '') . ' ' . ($data['make'] ?? '') . ' ' . ($data['model'] ?? ''),
                'data_completeness' => round($completeness, 1) . '%',
                'present_fields' => array_values($presentFields),
                'missing_fields' => array_values($missingFields),
                'image_count' => $imageCount,
                'has_documents' => $hasDocuments,
                'recommendations' => [],
            ];

            if ($completeness < 100) {
                $analysis['recommendations'][] = 'Fill in missing fields: ' . implode(', ', $missingFields);
            }
            if ($imageCount === 0) {
                $analysis['recommendations'][] = 'Add photos of the vehicle (recommended: 10-20 images from multiple angles).';
            } elseif ($imageCount < 5) {
                $analysis['recommendations'][] = "Only {$imageCount} image(s) uploaded. Add more for better visibility (recommended: 10+).";
            }
            if (empty($data['description'])) {
                $analysis['recommendations'][] = 'Generate a marketing description using the generate_description tool.';
            }
            if (empty($data['features'])) {
                $analysis['recommendations'][] = 'Add vehicle features to improve listing quality.';
            }
            if ($item->status === 'draft') {
                $analysis['recommendations'][] = 'Item is in draft status. Publish when ready.';
            }

            return [
                ['type' => 'text', 'text' => json_encode($analysis, JSON_PRETTY_PRINT)],
            ];
        } catch (\Exception $e) {
            return [
                ['type' => 'text', 'text' => json_encode([
                    'success' => false,
                    'error' => 'Analysis failed: ' . $e->getMessage(),
                ])],
            ];
        }
    }
}
