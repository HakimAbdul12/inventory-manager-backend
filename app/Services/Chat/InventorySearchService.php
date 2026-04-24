<?php

namespace App\Services\Chat;

use App\Models\InventoryItem;
use App\Models\WorkspaceChatConfig;
use Illuminate\Support\Facades\Log;

class InventorySearchService
{
    protected ExternalInventoryService $externalService;
    protected \App\Services\EmbeddingService $embeddingService;

    public function __construct(ExternalInventoryService $externalService, \App\Services\EmbeddingService $embeddingService)
    {
        $this->externalService = $externalService;
        $this->embeddingService = $embeddingService;
    }

    /**
     * Search inventory based on a natural language message.
     * Routes to external API or local DB depending on tenant config.
     */
    public function searchFromMessage(string $message, string $tenantId, int $limit = 5, ?WorkspaceChatConfig $config = null): array
    {
        // Resolve config if not provided
        if (!$config) {
            $config = WorkspaceChatConfig::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenantId)
                ->first();
        }

        // External API takes priority when enabled
        if ($config && $config->hasExternalApi()) {
            $filters = ['_query' => $message];
            return $this->externalService->search($config, $filters, $limit);
        }

        // Smart Internal (Vector Search)
        $queryEmbedding = $this->embeddingService->generateEmbedding($message);
        if ($queryEmbedding) {
            $vectorString = '[' . implode(',', $queryEmbedding) . ']';

            // Detect brand and model intent from the user's message
            $intent = $this->detectSearchIntent($message, $tenantId);

            $query = InventoryItem::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenantId)
                ->where('status', InventoryItem::STATUS_PUBLISHED)
                ->whereNotNull('embedding')
                ->with(['images']);

            // Strict brand filter when a specific make is detected
            if (!empty($intent['makes'])) {
                $query->where(function ($q) use ($intent) {
                    foreach ($intent['makes'] as $make) {
                        $q->orWhere('vector_string', 'ILIKE', '%' . $make . '%');
                    }
                });
            }

            // Build ranking expression with tiered boosting:
            // - Model match gets a heavy boost (-0.5) to ensure precise results
            // - Brand match gets a medium boost (-0.15) for general relevance
            // - Other keyword matches get a lighter boost (-0.1)
            $rankExpression = "(embedding <=> ?::vector)";
            $bindings = [$vectorString];

            // Heavy boost for detected model names (e.g. "m5", "x7", "c300")
            foreach ($intent['models'] as $model) {
                $rankExpression .= " - (CASE WHEN vector_string ILIKE ? THEN 0.5 ELSE 0 END)";
                $bindings[] = '%' . $model . '%';
            }

            // Medium boost for detected makes
            foreach ($intent['makes'] as $make) {
                $rankExpression .= " - (CASE WHEN vector_string ILIKE ? THEN 0.15 ELSE 0 END)";
                $bindings[] = '%' . $make . '%';
            }

            // Light boost for other significant keywords (e.g. "luxury", "sedan", "sports")
            foreach ($intent['keywords'] as $word) {
                $rankExpression .= " - (CASE WHEN vector_string ILIKE ? THEN 0.1 ELSE 0 END)";
                $bindings[] = '%' . $word . '%';
            }

            // Fetch more than needed so we can deduplicate effectively
            $fetchLimit = $limit * 3;
            $items = (clone $query)->orderByRaw($rankExpression, $bindings)
                ->limit($fetchLimit)
                ->get();

            // Fallback: If strict brand filter yielded nothing, broaden to all inventory
            if ($items->isEmpty() && !empty($intent['makes'])) {
                $items = InventoryItem::withoutGlobalScope('tenant')
                    ->where('tenant_id', $tenantId)
                    ->where('status', InventoryItem::STATUS_PUBLISHED)
                    ->whereNotNull('embedding')
                    ->with(['images'])
                    ->orderByRaw($rankExpression, $bindings)
                    ->limit($fetchLimit)
                    ->get();

                Log::info("Strict brand search found no results. Falling back to alternatives for: {$message}");
            }

            if ($items->isNotEmpty()) {
                // Deduplicate: keep the best-scored item per unique make+model combo
                $items = $this->deduplicateResults($items, $limit);

                Log::info("Hybrid semantic search yielded results for query: {$message}", [
                    'detected_makes' => $intent['makes'],
                    'detected_models' => $intent['models'],
                    'keywords' => $intent['keywords'],
                    'result_count' => $items->count(),
                ]);
                return $this->formatInventoryCards($items);
            }
        }

        // Legacy Keyword Fallback (Dynamic Text Matching)
        return $this->search($tenantId, $message, $limit);
    }

    /**
     * Detect brand names, model names, and remaining keywords from the user's message.
     * Returns an array with keys: makes, models, keywords.
     */
    protected function detectSearchIntent(string $message, string $tenantId): array
    {
        $messageLower = strtolower($message);

        // Fetch unique make+model pairs from inventory
        $inventory = InventoryItem::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->whereNotNull('embedding')
            ->get(['generated_data']);

        $availableMakes = $inventory
            ->map(fn($i) => strtolower($i->generated_data['make'] ?? ''))
            ->filter()->unique()->values()->toArray();

        $availableModels = $inventory
            ->map(fn($i) => strtolower($i->generated_data['model'] ?? ''))
            ->filter()->unique()->values()->toArray();

        // Detect makes (including partial like "mercedes" matching "mercedes-benz")
        $detectedMakes = [];
        foreach ($availableMakes as $make) {
            $shortMake = head(explode('-', $make));
            if (str_contains($messageLower, $make) || str_contains($messageLower, $shortMake)) {
                $detectedMakes[] = $make;
            }
        }

        // Detect models (e.g. "m5", "x7", "c300", "m5cs")
        $detectedModels = [];
        foreach ($availableModels as $model) {
            if (str_contains($messageLower, $model)) {
                $detectedModels[] = $model;
            }
        }

        // Remaining keywords: extract meaningful words not already matched as make/model
        $allMatchedTerms = array_merge($detectedMakes, $detectedModels);
        $words = array_filter(
            explode(' ', strtolower(preg_replace('/[^a-z0-9\s]/', '', $message))),
            function ($w) use ($allMatchedTerms) {
                if (strlen($w) <= 2) return false;
                // Skip words already captured as a make or model
                foreach ($allMatchedTerms as $term) {
                    if (str_contains($term, $w) || str_contains($w, $term)) return false;
                }
                return true;
            }
        );

        return [
            'makes' => $detectedMakes,
            'models' => $detectedModels,
            'keywords' => array_values($words),
        ];
    }

    /**
     * Deduplicate results: keep the best-scored item per unique make+model,
     * then backfill remaining slots with alternatives for diversity.
     */
    protected function deduplicateResults($items, int $limit): \Illuminate\Support\Collection
    {
        $seen = [];
        $unique = collect();
        $duplicates = collect();

        foreach ($items as $item) {
            $data = $item->generated_data ?? [];
            $key = strtolower(trim(($data['make'] ?? '') . '|' . ($data['model'] ?? '')));

            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique->push($item);
            } else {
                $duplicates->push($item);
            }
        }

        // If we have enough unique items, return those
        if ($unique->count() >= $limit) {
            return $unique->take($limit);
        }

        // Otherwise backfill with duplicates (best-scored first since DB already ordered them)
        return $unique->merge($duplicates)->take($limit);
    }

    /**
     * Search inventory leveraging generated vector_string for generic dynamic matching.
     */
    public function search(string $tenantId, string $message = '', int $limit = 5): array
    {
        $query = InventoryItem::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('status', InventoryItem::STATUS_PUBLISHED)
            ->with(['images']);

        if (!empty($message)) {
            // Very simple fallback: look for words inside the vector string
            $words = array_filter(explode(' ', strtolower(preg_replace('/[^a-z0-9\s]/', '', $message))), function ($w) {
                // Ignore very short stop words
                return strlen($w) > 2;
            });

            if (!empty($words)) {
                $query->where(function ($q) use ($words) {
                    foreach ($words as $word) {
                        $q->orWhere('vector_string', 'ILIKE', '%' . $word . '%');
                    }
                });
            }
        }

        $items = $query->limit($limit)->get();
        return $this->formatInventoryCards($items);
    }

    /**
     * Format inventory items as standard AI widget cards.
     * Extracts properties dynamically from generated_data instead of hardcoded models.
     */
    protected function formatInventoryCards($items): array
    {
        $config = WorkspaceChatConfig::withoutGlobalScope('tenant')
            ->where('tenant_id', $items->first()?->tenant_id)
            ->first();
        
        $urlTemplate = $config?->widget_settings['vdp_url_template'] ?? null;

        return $items->map(function (InventoryItem $item) use ($urlTemplate) {
            $data = $item->generated_data ?? [];

            $primaryImage = $item->images->firstWhere('is_primary', true);
            $image = $primaryImage?->url ?? $data['image_url'] ?? null;

            $vdpUrl = null;
            if ($urlTemplate) {
                $vdpUrl = $urlTemplate;
                // Combine id and generated_data for placeholders
                $placeholderData = array_merge(
                    ['id' => $item->id, 'system_id' => $item->id],
                    $data
                );
                
                foreach ($placeholderData as $key => $value) {
                    if (is_scalar($value)) {
                        $vdpUrl = str_replace('{{' . $key . '}}', (string)$value, $vdpUrl);
                    }
                }
            }

            // Derive dynamic representation values
            $title = $item->title ?? $data['title'] ?? trim(($data['make'] ?? '') . ' ' . ($data['model'] ?? ''));
            if (empty($title)) {
                $title = "Item #" . substr($item->id, 0, 8);
            }

            $priceRaw = $data['price'] ?? null;
            $priceFormatted = is_numeric($priceRaw) ? number_format((float)$priceRaw, 0) : null;
            
            $mileageRaw = $data['mileage'] ?? null;
            $mileageFormatted = is_numeric($mileageRaw) ? number_format((float)$mileageRaw, 0) : null;

            return [
                'id' => $item->id,
                'title' => $title,
                'price' => $priceFormatted,
                'price_raw' => $priceRaw,
                'image_url' => $image,
                'status' => $data['status'] ?? 'available',
                'vdp_url' => $vdpUrl,
                // Include typical fallback metadata cleanly
                'year' => $data['year'] ?? null,
                'make' => $data['make'] ?? null,
                'model' => $data['model'] ?? null,
                'trim' => $data['trim'] ?? null,
                'mileage' => $mileageFormatted,
                'cta' => [
                    ['label' => 'More Details', 'action' => 'view_details', 'url' => $vdpUrl],
                    ['label' => 'Ask Question', 'action' => 'inquire'],
                ],
            ];
        })->filter()->values()->toArray();
    }
}
