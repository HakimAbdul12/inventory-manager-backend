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
            $filters = ['_query' => $message]; // pass raw message for search param
            return $this->externalService->search($config, $filters, $limit);
        }

        // Smart Internal (Vector Search)
        $queryEmbedding = $this->embeddingService->generateEmbedding($message);
        if ($queryEmbedding) {
            $vectorString = '[' . implode(',', $queryEmbedding) . ']';
            
            // Keyword Boost: Extract potential keywords from message to rerank matches
            $keywords = array_filter(explode(' ', strtolower(preg_replace('/[^a-z0-9\s]/', '', $message))), function ($w) {
                return strlen($w) > 3; // Focus on significant words (e.g. "Mercedes", "Sedan")
            });

            // 1. Identify if a specific Brand (Make) is requested
            $requestedMakes = $this->identifyRequestedMakes($message, $tenantId);

            $query = InventoryItem::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenantId)
                ->where('status', InventoryItem::STATUS_PUBLISHED)
                ->whereNotNull('embedding')
                ->with(['images']);

            // 2. Strict Filter: If brands are detected, only search within those brands
            if (!empty($requestedMakes)) {
                $query->where(function($q) use ($requestedMakes) {
                    foreach ($requestedMakes as $make) {
                        $q->orWhere('vector_string', 'ILIKE', '%' . $make . '%');
                    }
                });
            }

            // Build dynamic ranking expression
            // Base rank is the semantic distance (lower is closer)
            // Added parentheses to ensure correct operator precedence (distance - boost)
            $rankExpression = "(embedding <=> ?::vector)";
            $bindings = [$vectorString];

            // Add boosts for exact keyword matches in the vector_string
            foreach ($keywords as $word) {
                // If the word matches, we subtract from the distance (effectively boosting it)
                $rankExpression .= " - (CASE WHEN vector_string ILIKE ? THEN 0.15 ELSE 0 END)";
                $bindings[] = '%' . $word . '%';
            }

            $items = (clone $query)->orderByRaw($rankExpression, $bindings)
                ->limit($limit)
                ->get();
                
            // 3. Fallback: If strict filtered search is empty, broaden search to show alternatives
            if ($items->isEmpty() && !empty($requestedMakes)) {
                $items = InventoryItem::withoutGlobalScope('tenant')
                    ->where('tenant_id', $tenantId)
                    ->where('status', InventoryItem::STATUS_PUBLISHED)
                    ->whereNotNull('embedding')
                    ->with(['images'])
                    ->orderByRaw($rankExpression, $bindings)
                    ->limit($limit)
                    ->get();
                
                Log::info("Strict brand search found no results. Falling back to semantic alternatives for: {$message}");
            }

            if ($items->isNotEmpty()) {
                Log::info("Hybrid semantic search yielded results for query: {$message}", [
                    'keywords' => $keywords,
                    'detected_makes' => $requestedMakes
                ]);
                return $this->formatInventoryCards($items);
            }
        }

        // Legacy Keyword Fallback (Dynamic Text Matching)
        return $this->search($tenantId, $message, $limit);
    }

    /**
     * Identifies known inventory brand names within a user's message.
     */
    protected function identifyRequestedMakes(string $message, string $tenantId): array
    {
        // Cache this for performance if inventory is large
        $availableMakes = InventoryItem::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->whereNotNull('embedding')
            ->get()
            ->map(function ($item) {
                return $item->generated_data['make'] ?? null;
            })
            ->filter()
            ->unique()
            ->map(fn($m) => strtolower($m))
            ->toArray();

        $detected = [];
        $messageLower = strtolower($message);

        foreach ($availableMakes as $make) {
            // Handle variations like "Mercedes-Benz" vs user typing "Mercedes"
            $shortMake = head(explode('-', $make));
            if (str_contains($messageLower, $make) || str_contains($messageLower, $shortMake)) {
                $detected[] = $make;
            }
        }

        return $detected;
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
