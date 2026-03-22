<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryVideo extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'inventory_item_id',
        'type', // 'upload' or 'link'
        'url',
        'path',
        'title',
    ];

    protected $appends = ['preview_url'];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * Get the absolute URL for the video (either linked URL or uploaded storage URL)
     */
    public function getPreviewUrlAttribute(): ?string
    {
        if ($this->type === 'link') {
            return $this->url;
        }

        if ($this->path) {
            return config('app.url') . \Illuminate\Support\Facades\Storage::url($this->path);
        }

        return null;
    }
}
