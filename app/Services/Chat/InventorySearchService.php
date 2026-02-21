<?php

namespace App\Services\Chat;

use App\Models\InventoryItem;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Log;

class InventorySearchService
{
    /**
     * Search inventory based on a natural language message.
     * Returns formatted vehicle cards for the chat widget.
     */
    public function searchFromMessage(string $message, string $tenantId, int $limit = 5): array
    {
        $filters = $this->extractFilters($message);

        return $this->search($tenantId, $filters, $limit);
    }

    /**
     * Search inventory with explicit filters.
     */
    public function search(string $tenantId, array $filters = [], int $limit = 5): array
    {
        $query = InventoryItem::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('status', InventoryItem::STATUS_PUBLISHED)
            ->with(['vehicle', 'images']);

        // Join vehicles for filtering
        $query->whereHas('vehicle', function ($q) use ($filters) {
            if (!empty($filters['make'])) {
                $q->where('make', 'LIKE', "%{$filters['make']}%");
            }
            if (!empty($filters['model'])) {
                $q->where('model', 'LIKE', "%{$filters['model']}%");
            }
            if (!empty($filters['year_min'])) {
                $q->where('year', '>=', $filters['year_min']);
            }
            if (!empty($filters['year_max'])) {
                $q->where('year', '<=', $filters['year_max']);
            }
            if (!empty($filters['price_min'])) {
                $q->where('price', '>=', $filters['price_min']);
            }
            if (!empty($filters['price_max'])) {
                $q->where('price', '<=', $filters['price_max']);
            }
            if (!empty($filters['body_type'])) {
                // body_type would be in generated_data, but filter on vehicle table if available
                $q->where('model', 'LIKE', "%{$filters['body_type']}%");
            }
        });

        $items = $query->limit($limit)->get();

        // If no results, try a broader search
        if ($items->isEmpty() && !empty($filters)) {
            $items = $this->broadSearch($tenantId, $filters, $limit);
        }

        return $this->formatVehicleCards($items);
    }

    /**
     * Broader search when exact filters yield no results — suggests similar vehicles.
     */
    protected function broadSearch(string $tenantId, array $filters, int $limit): \Illuminate\Support\Collection
    {
        $query = InventoryItem::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('status', InventoryItem::STATUS_PUBLISHED)
            ->with(['vehicle', 'images']);

        // Try with just make or just price range
        if (!empty($filters['make'])) {
            $query->whereHas('vehicle', function ($q) use ($filters) {
                $q->where('make', 'LIKE', "%{$filters['make']}%");
            });
        } elseif (!empty($filters['price_max'])) {
            $query->whereHas('vehicle', function ($q) use ($filters) {
                $q->where('price', '<=', $filters['price_max'] * 1.2); // 20% tolerance
            });
        }

        return $query->limit($limit)->get();
    }

    /**
     * Format inventory items as vehicle cards for the widget.
     */
    protected function formatVehicleCards($items): array
    {
        return $items->map(function (InventoryItem $item) {
            $vehicle = $item->vehicle;
            if (!$vehicle) {
                return null;
            }

            $primaryImage = $item->images->firstWhere('is_primary', true);
            $image = $primaryImage?->url ?? $item->images->first()?->url;

            return [
                'id' => $item->id,
                'year' => $vehicle->year,
                'make' => $vehicle->make,
                'model' => $vehicle->model,
                'trim' => $vehicle->trim,
                'price' => number_format($vehicle->price, 0),
                'price_raw' => $vehicle->price,
                'mileage' => number_format($vehicle->mileage, 0),
                'image_url' => $image,
                'status' => $vehicle->status ?? 'available',
                'title' => $item->title,
                'cta' => [
                    ['label' => 'Book Test Drive', 'action' => 'test_drive'],
                    ['label' => 'Request Financing', 'action' => 'financing'],
                    ['label' => 'View Details', 'action' => 'view_details'],
                ],
            ];
        })->filter()->values()->toArray();
    }

    /**
     * Extract search filters from a natural language message.
     */
    protected function extractFilters(string $message): array
    {
        $lower = strtolower($message);
        $filters = [];

        // Extract make
        $makes = [
            'toyota',
            'honda',
            'ford',
            'chevrolet',
            'bmw',
            'mercedes',
            'audi',
            'tesla',
            'nissan',
            'hyundai',
            'kia',
            'volkswagen',
            'subaru',
            'mazda',
            'lexus',
            'jeep',
            'dodge',
            'ram',
            'gmc',
            'cadillac',
            'volvo',
            'porsche',
            'land rover',
            'jaguar',
            'infiniti',
            'acura',
            'genesis',
            'lincoln'
        ];

        foreach ($makes as $make) {
            if (str_contains($lower, $make)) {
                $filters['make'] = ucfirst($make);
                break;
            }
        }

        // Extract price constraints
        if (preg_match('/under\s*\$?([\d,]+)/i', $message, $m)) {
            $filters['price_max'] = (int) str_replace(',', '', $m[1]);
        }
        if (preg_match('/over\s*\$?([\d,]+)/i', $message, $m)) {
            $filters['price_min'] = (int) str_replace(',', '', $m[1]);
        }
        if (preg_match('/between\s*\$?([\d,]+)\s*(?:and|-)\s*\$?([\d,]+)/i', $message, $m)) {
            $filters['price_min'] = (int) str_replace(',', '', $m[1]);
            $filters['price_max'] = (int) str_replace(',', '', $m[2]);
        }

        // Extract year
        if (preg_match('/\b(20[1-2]\d)\b/', $message, $m)) {
            $filters['year_min'] = (int) $m[1];
            $filters['year_max'] = (int) $m[1];
        }

        // Extract body type
        $bodyTypes = ['suv', 'sedan', 'truck', 'coupe', 'van', 'hatchback', 'convertible', 'wagon'];
        foreach ($bodyTypes as $type) {
            if (str_contains($lower, $type)) {
                $filters['body_type'] = $type;
                break;
            }
        }

        return $filters;
    }
}
