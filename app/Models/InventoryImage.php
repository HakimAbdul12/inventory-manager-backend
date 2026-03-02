<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryImage extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'inventory_item_id',
        'path',
        'prompt',
        'generated_by',
        'alt',
        'is_primary',
        'processing_status',
        'sizes',
    ];

    protected $appends = ['url'];

    protected $casts = [
        'is_primary' => 'boolean',
        'sizes' => 'array',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * Get the absolute URL for the image.
     */
    public function getUrlAttribute(): string
    {
        if (filter_var($this->path, FILTER_VALIDATE_URL)) {
            return $this->path;
        }

        return config('app.url') . \Illuminate\Support\Facades\Storage::url($this->path);
    }
}
