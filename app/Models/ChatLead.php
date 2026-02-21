<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatLead extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $table = 'chat_leads';

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'conversation_id',
        'name',
        'email',
        'phone',
        'intent',
        'interested_vehicle_id',
        'notes',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected $attributes = [
        'intent' => 'general',
    ];

    public const INTENT_TEST_DRIVE = 'test_drive';
    public const INTENT_FINANCING = 'financing';
    public const INTENT_CONTACT_SALES = 'contact_sales';
    public const INTENT_GENERAL = 'general';

    public const INTENTS = [
        self::INTENT_TEST_DRIVE,
        self::INTENT_FINANCING,
        self::INTENT_CONTACT_SALES,
        self::INTENT_GENERAL,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $lead) {
            if (empty($lead->created_at)) {
                $lead->created_at = now();
            }
        });
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    public function interestedVehicle(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'interested_vehicle_id');
    }
}
