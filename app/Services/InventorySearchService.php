<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;

class InventorySearchService
{
    /**
     * Search inventory items with fuzzy matching on multiple fields.
     * Supports: ID, VIN, and natural language queries like "2026 bmw m5cs awd"
     * @param string $query Search query
     * @param int $perPage Results per page
     * @param int $page Current page
     * @return array{items: array, total: int, per_page: int, current_page: int
     */
    public function search(string $query, int $perPage = 10, int $page = 1): array
    {
        $query = trim($query);

        if (empty($query)) {
            return [
                'items' => [],
                'total' => 0,
                'per_page' => $perPage,
                'current_page' => $page,
            ];
        }

        // Start with base query (already scoped by tenant in controller)
        $baseQuery = InventoryItem::with(['images', 'category', 'vehicle']);

        // Calculate relevance scores for each result
        $items = $baseQuery->get();

        // Score items based on relevance
        $scoredItems = $items->map(fn ($item) => [
            'item' => $item,
            'score' => $this->calculateRelevanceScore($item, $query),
        ])
            ->filter(fn ($scored) => $scored['score'] > 0)
            ->sortByDesc('score')
            ->values();

        // Apply pagination manually
        $total = $scoredItems->count();
        $paginatedItems = $scoredItems
            ->slice(($page - 1) * $perPage, $perPage)
            ->map(fn ($scored) => $this->formatSearchResult($scored['item'], $query))
            ->values();

        return [
            'items' => $paginatedItems->toArray(),
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
        ];
    }

    /**
     * Calculate relevance score for an inventory item against search query.
     * @param InventoryItem $item
     * @param string $query
     * @return float Score between 0 and infinity (0 = no match)
     */
    private function calculateRelevanceScore(InventoryItem $item, string $query): float
    {
        $query = strtolower(trim($query));
        $score = 0;

        // 1. EXACT ID MATCH (highest priority)
        if (strcasecmp($item->id, $query) === 0) {
            return 1000;
        }

        // 2. VIN EXACT MATCH
        $vehicle = $item->vehicle;
        if ($vehicle && strcasecmp($vehicle->vin, $query) === 0) {
            return 900;
        }

        // 3. VIN PARTIAL MATCH (fuzzy)
        if ($vehicle && !empty($vehicle->vin)) {
            $vinScore = $this->fuzzyMatchScore($vehicle->vin, $query);
            $score = max($score, $vinScore * 100);
        }

        // 4. NATURAL LANGUAGE QUERY (e.g., "2026 bmw m5cs awd")
        $vehicleScore = $this->matchVehicleQuery($item, $query);
        $score = max($score, $vehicleScore * 100);

        // 5. TITLE/DESCRIPTION MATCH
        $titleScore = $this->fuzzyMatchScore($item->title ?? '', $query);
        $score = max($score, $titleScore * 50);

        // 6. GENERATED DATA FIELDS
        $data = $item->generated_data ?? [];
        foreach ($data as $field => $value) {
            if (is_string($value)) {
                $fieldScore = $this->fuzzyMatchScore($value, $query);
                if ($fieldScore > 0) {
                    $score = max($score, $fieldScore * 30);
                }
            }
        }

        return $score;
    }

    /**
     * Match natural language vehicle queries like "2026 bmw m5cs awd".
     * @param InventoryItem $item
     * @param string $query
     * @return float Score between 0 and 1
     */
    private function matchVehicleQuery(InventoryItem $item, string $query): float
    {
        $vehicle = $item->vehicle;
        $data = $item->generated_data ?? [];

        if (!$vehicle && empty($data)) {
            return 0;
        }

        // Extract potential vehicle keywords from query
        $queryParts = preg_split('/\s+/', strtolower($query));
        $year = null;
        $make = null;
        $model = null;
        $trim = null;
        $otherKeywords = [];

        foreach ($queryParts as $part) {
            // Check if it's a year (4 digits, 1900-2099)
            if (preg_match('/^(19|20)\d{2}$/', $part)) {
                $year = intval($part);
            } elseif (in_array($part, ['awd', '4wd', 'fwd', 'rwd'])) {
                // Drive type pattern
                $otherKeywords[] = $part;
            } elseif (strlen($part) >= 2) {
                // Could be make, model, or trim
                $otherKeywords[] = $part;
            }
        }

        $matchCount = 0;
        $totalCriteria = 0;

        // Year check
        if ($year !== null) {
            $totalCriteria++;
            $vehicleYear = $vehicle?->year ?? $data['year'] ?? null;
            if ($vehicleYear && intval($vehicleYear) === $year) {
                $matchCount++;
            }
        }

        // Make check (first significant keyword if year found, or first keyword)
        if (!empty($otherKeywords)) {
            $makeCandidate = $otherKeywords[0];
            $vehicleMake = strtolower($vehicle?->make ?? $data['make'] ?? '');
            if (!empty($vehicleMake)) {
                $totalCriteria++;
                if (stripos($vehicleMake, $makeCandidate) !== false || stripos($makeCandidate, $vehicleMake) !== false) {
                    $matchCount++;
                }

                // Model check (second keyword)
                if (count($otherKeywords) > 1) {
                    $modelCandidate = $otherKeywords[1];
                    $vehicleModel = strtolower($vehicle?->model ?? $data['model'] ?? '');
                    if (!empty($vehicleModel)) {
                        $totalCriteria++;
                        if (stripos($vehicleModel, $modelCandidate) !== false || stripos($modelCandidate, $vehicleModel) !== false) {
                            $matchCount++;
                        }

                        // Trim/variant check (remaining keywords)
                        if (count($otherKeywords) > 2) {
                            $trimCandidate = implode(' ', array_slice($otherKeywords, 2));
                            $vehicleTrim = strtolower($vehicle?->trim ?? $data['trim'] ?? '');
                            if (!empty($vehicleTrim)) {
                                $totalCriteria++;
                                if (stripos($vehicleTrim, $trimCandidate) !== false || stripos($trimCandidate, $vehicleTrim) !== false) {
                                    $matchCount++;
                                }
                            }
                        }
                    }
                }
            }
        }

        // Return ratio of matched criteria (min 1 to count as match)
        if ($totalCriteria === 0) {
            return 0;
        }

        return min(1, $matchCount / $totalCriteria);
    }

    /**
     * Fuzzy string matching using Levenshtein distance with threshold.
     * Returns higher scores for closer matches.
     * @param string $subject
     * @param string $query
     * @return float Score between 0 and 1
     */
    private function fuzzyMatchScore(string $subject, string $query): float
    {
        $subject = strtolower(trim($subject));
        $query = strtolower(trim($query));

        // Exact match
        if ($subject === $query) {
            return 1.0;
        }

        // Substring match
        if (stripos($subject, $query) !== false) {
            return 0.9;
        }

        // Levenshtein distance
        $distance = levenshtein($query, $subject);

        // Don't match if distance is too large
        $maxDistance = max(strlen($query), strlen($subject)) * 0.33; // 33% tolerance
        if ($distance > $maxDistance) {
            return 0;
        }

        // Score: 1 - normalized distance
        return max(0, 1 - ($distance / max(strlen($query), strlen($subject))));
    }

    /**
     * Format search result for API response.
     * @param InventoryItem $item
     * @param string $query Search query for highlighting
     * @return array
     */
    private function formatSearchResult(InventoryItem $item, string $query): array
    {
        $vehicle = $item->vehicle;
        $image = $item->images->firstWhere('is_primary', true) ?? $item->images->first();

        // Build the primary image with correct URL
        $primaryImage = null;
        if ($image) {
            $imageUrl = $image->url; // Uses the `url` accessor which handles relative paths
            $primaryImage = [
                'id' => $image->id,
                'path' => $imageUrl,
                'thumbnail' => $image->thumbnail ?? $imageUrl,
                'alt' => $image->alt,
                'is_primary' => $image->is_primary,
                'processing_status' => $image->processing_status,
            ];
        }

        return [
            'id' => $item->id,
            'title' => $item->title,
            'category' => $item->category?->name,
            'category_slug' => $item->category?->slug,
            'status' => $item->status,
            'primaryImage' => $primaryImage,
            'price' => $item->getField('price'),
            'mileage' => $item->getField('mileage'),
            'year' => $item->getField('year'),
            'make' => $item->getField('make'),
            'model' => $item->getField('model'),
            'condition' => $item->getField('condition'),
            'color' => $item->getField('color'),
            'vin' => $vehicle?->vin ?? $item->getField('vin'),
            'createdAt' => $item->created_at?->toIso8601String(),
        ];
    }
}
