<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class InventoryItem extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $fillable = [
        'process_id',
        'user_id',
        'category_id',
        'tenant_id',
        'status',
        'generated_data',
        'metadata',
        'confidence_score',
        'analysis_results',
    ];

    protected $casts = [
        'generated_data' => 'array',
        'metadata' => 'array',
        'confidence_score' => 'integer',
        'analysis_results' => 'array',
    ];

    protected $attributes = [
        'status' => 'draft',
        'generated_data' => '{}',
        'metadata' => '{}',
        'analysis_results' => '{}',
    ];

    /**
     * Possible statuses for inventory items.
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    /**
     * Get the process that created this item.
     */
    public function process(): BelongsTo
    {
        return $this->belongsTo(InventoryProcess::class, 'process_id');
    }

    /**
     * Get the user that owns the item.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the price history for this inventory item.
     */
    public function priceHistories(): HasMany
    {
        return $this->hasMany(InventoryPriceHistory::class)->orderBy('created_at', 'desc');
    }

    /**
     * Record a price change for this item.
     */
    public function recordPriceChange(?float $oldPrice, float $newPrice, string $source = 'manual', ?string $userId = null, ?string $notes = null): InventoryPriceHistory
    {
        return $this->priceHistories()->create([
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
            'changed_by' => $userId ?? auth()->id(),
            'source' => $source,
            'notes' => $notes,
        ]);
    }

    /**
     * Get the category for this item.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the vehicle associated with this inventory item.
     */
    public function vehicle(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Vehicle::class, 'inventory_item_id');
    }

    /**
     * Get the images for the item.
     */
    public function images(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(InventoryImage::class)->orderBy('is_primary', 'desc');
    }

    /**
     * Get the videos for the item.
     */
    public function videos()
    {
        return $this->hasMany(InventoryVideo::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get the documents for the item.
     */
    public function documents()
    {
        return $this->hasMany(InventoryDocument::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get the primary image.
     */
    public function getPrimaryImageAttribute(): ?InventoryImage
    {
        return $this->images->firstWhere('is_primary', true);
    }

    /**
     * Get a specific field from generated data.
     */
    public function getField(string $key, $default = null)
    {
        return $this->generated_data[$key] ?? $default;
    }

    /**
     * Set a specific field in generated data.
     */
    public function setField(string $key, $value): void
    {
        $data = $this->generated_data ?? [];
        $data[$key] = $value;
        $this->update(['generated_data' => $data]);
    }



    /**
     * Publish the item.
     */
    public function publish(): void
    {
        $this->update(['status' => self::STATUS_PUBLISHED]);
    }

    /**
     * Archive the item.
     */
    public function archive(): void
    {
        $this->update(['status' => self::STATUS_ARCHIVED]);
    }

    /**
     * Scope to published items only.
     */
    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    /**
     * Get formatted title from generated data.
     */
    public function getTitleAttribute(): string
    {
        $data = $this->generated_data ?? [];

        // For cars category
        if (isset($data['year'], $data['make'], $data['model'])) {
            return "{$data['year']} {$data['make']} {$data['model']}";
        }

        // Fallback
        return $data['title'] ?? "Inventory #{$this->id}";
    }

    // --- NEW RELATIONSHIPS FOR ADVANCED DEALERSHIP FEATURES ---

    public function leads(): BelongsToMany
    {
        return $this->belongsToMany(Lead::class, 'inventory_item_lead');
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    public function reconditioningTasks(): HasMany
    {
        return $this->hasMany(ReconditioningTask::class);
    }

    public function serviceRecords(): HasMany
    {
        return $this->hasMany(ServiceRecord::class);
    }

    public function publishingStatuses(): HasMany
    {
        return $this->hasMany(InventoryPublishingStatus::class);
    }

    // --- DERIVED METRICS ---

    public function getTotalReconditioningCostAttribute(): float
    {
        $tasksCost = $this->reconditioningTasks()->where('status', 'completed')->sum('cost');
        $servicesCost = $this->serviceRecords()->sum('cost');
        
        return (float) ($tasksCost + $servicesCost);
    }

    public function getTotalAcvAttribute(): float
    {
        $data = $this->generated_data ?? [];
        $purchasePrice = (float) ($data['purchase_price'] ?? 0);
        $transportCost = (float) ($data['transport_cost'] ?? 0);
        $auctionFees = (float) ($data['auction_fees'] ?? 0);
        $inspectionCost = (float) ($data['inspection_cost'] ?? 0);

        return $purchasePrice + $transportCost + $auctionFees + $inspectionCost + $this->total_reconditioning_cost;
    }

    public function getGrossProfitAttribute(): ?float
    {
        // Gross Profit = Sale Price - Total ACV
        // Use the first closed deal sale price if available, otherwise the current listed price
        $closedDeal = $this->deals()->where('status', 'closed')->first();
        
        if ($closedDeal && $closedDeal->sale_price !== null) {
            $revenue = (float) $closedDeal->sale_price;
        } else {
            $data = $this->generated_data ?? [];
            if (!isset($data['price'])) {
                return null;
            }
            $revenue = (float) $data['price'];
        }

        return $revenue - $this->total_acv;
    }

    public function getNetProfitAttribute(): ?float
    {
        $closedDeal = $this->deals()->where('status', 'closed')->first();
        if (!$closedDeal) {
            return null; // Net profit usually only calculated on closed deal
        }

        // Net Profit = Total Deal Amount (incl fees) - Taxes - Total ACV
        // Assuming fees are retained profit and taxes are paid out. This depends on dealership accounting,
        // but typically Net = Gross + Fees - Pack/Other Costs. We will use a basic formula:
        $revenue = (float) $closedDeal->total_amount - (float) $closedDeal->taxes;
        return $revenue - $this->total_acv;
    }
}
