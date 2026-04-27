<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryPriceHistory extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'inventory_item_id',
        'old_price',
        'new_price',
        'changed_by',
        'source',
        'notes',
    ];

    protected $casts = [
        'old_price' => 'decimal:2',
        'new_price' => 'decimal:2',
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function scopeForItem(Builder $query, string $itemId): Builder
    {
        return $query->where('inventory_item_id', $itemId);
    }
}
