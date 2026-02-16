<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'inventory_item_id',
        'user_id',
        'vin',
        'make',
        'model',
        'year',
        'trim',
        'mileage',
        'price',
        'market_average',
        'velocity_score',
        'carrying_cost',
        'dol',
        'status',
    ];

    protected $casts = [
        'year' => 'integer',
        'mileage' => 'integer',
        'price' => 'decimal:2',
        'market_average' => 'decimal:2',
        'velocity_score' => 'decimal:2',
        'carrying_cost' => 'decimal:2',
        'dol' => 'integer',
    ];

    /**
     * Get the inventory item associated with the vehicle.
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * Get the user that owns the vehicle.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the pricing history for the vehicle.
     */
    public function pricingHistories(): HasMany
    {
        return $this->hasMany(PricingHistory::class);
    }

    /**
     * Get the compliance audits for the vehicle.
     */
    public function complianceAudits(): HasMany
    {
        return $this->hasMany(ComplianceAudit::class);
    }
}
