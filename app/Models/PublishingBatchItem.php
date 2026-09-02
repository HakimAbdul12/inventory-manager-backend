<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublishingBatchItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'batch_id',
        'inventory_item_id',
        'platform_key',
        'format',
        'status',
        'attempts',
        'max_attempts',
        'error_message',
        'last_attempted_at',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'max_attempts' => 'integer',
        'last_attempted_at' => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(PublishingBatch::class, 'batch_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
