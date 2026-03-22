<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryDocument extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'inventory_item_id',
        'name',
        'type', // report, manual, invoice, etc.
        'path',
        'mime_type',
        'size',
    ];

    protected $appends = ['url'];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * Get the absolute URL for the document.
     */
    public function getUrlAttribute(): string
    {
        if (filter_var($this->path, FILTER_VALIDATE_URL)) {
            return $this->path;
        }

        return config('app.url') . \Illuminate\Support\Facades\Storage::url($this->path);
    }
}
