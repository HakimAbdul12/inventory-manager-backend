<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'process_id',
        'user_id',
        'category_id',
        'status',
        'generated_data',
        'metadata',
    ];

    protected $casts = [
        'generated_data' => 'array',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'status' => 'draft',
        'generated_data' => '{}',
        'metadata' => '{}',
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
     * Get the category for this item.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the images for the item.
     */
    public function images(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(InventoryImage::class)->orderBy('is_primary', 'desc');
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
}
