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
        'provider_name',
        'vehicle_details',
        'source',
        'status',
        'external_reference_id',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'vehicle_details' => 'array',
    ];

    protected $attributes = [
        'intent' => 'general',
        'source' => 'chat',
        'status' => 'new',
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

    public const SOURCE_CHAT = 'chat';
    public const SOURCE_EMAIL = 'email';

    public const STATUS_NEW = 'new';
    public const STATUS_CONTACTED = 'contacted';
    public const STATUS_CLOSED_WON = 'closed_won';
    public const STATUS_CLOSED_LOST = 'closed_lost';

    public const SOURCES = [
        self::SOURCE_CHAT,
        self::SOURCE_EMAIL,
    ];

    public const STATUSES = [
        self::STATUS_NEW,
        self::STATUS_CONTACTED,
        self::STATUS_CLOSED_WON,
        self::STATUS_CLOSED_LOST,
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
