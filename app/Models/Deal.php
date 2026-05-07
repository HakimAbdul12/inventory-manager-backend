<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deal extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'inventory_item_id',
        'lead_id',
        'status',
        'sale_price',
        'taxes',
        'fees',
        'total_amount',
        'financing_details',
        'trade_in_details',
    ];

    protected $casts = [
        'sale_price' => 'decimal:2',
        'taxes' => 'decimal:2',
        'fees' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'financing_details' => 'array',
        'trade_in_details' => 'array',
    ];

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
}
