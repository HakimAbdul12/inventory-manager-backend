<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    // ── Status Constants ──────────────────────────────────────

    public const STATUS_NEW = 'new';
    public const STATUS_CONTACTED = 'contacted';
    public const STATUS_ENGAGED = 'engaged';
    public const STATUS_APPOINTMENT_SCHEDULED = 'appointment_scheduled';
    public const STATUS_TEST_DRIVE_COMPLETED = 'test_drive_completed';
    public const STATUS_NEGOTIATING = 'negotiating';
    public const STATUS_CREDIT_APP_SENT = 'credit_app_sent';
    public const STATUS_DEAL_CREATED = 'deal_created';
    public const STATUS_SOLD = 'sold';
    public const STATUS_LOST = 'lost';
    public const STATUS_INACTIVE = 'inactive';

    public const STATUSES = [
        self::STATUS_NEW,
        self::STATUS_CONTACTED,
        self::STATUS_ENGAGED,
        self::STATUS_APPOINTMENT_SCHEDULED,
        self::STATUS_TEST_DRIVE_COMPLETED,
        self::STATUS_NEGOTIATING,
        self::STATUS_CREDIT_APP_SENT,
        self::STATUS_DEAL_CREATED,
        self::STATUS_SOLD,
        self::STATUS_LOST,
        self::STATUS_INACTIVE,
    ];

    // ── Source Type Constants ──────────────────────────────────

    public const SOURCE_WEBSITE = 'website';
    public const SOURCE_CHAT = 'chat';
    public const SOURCE_WALK_IN = 'walk_in';
    public const SOURCE_PHONE = 'phone';
    public const SOURCE_FACEBOOK = 'facebook';
    public const SOURCE_MARKETPLACE = 'marketplace';
    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_REFERRAL = 'referral';
    public const SOURCE_SERVICE = 'service';
    public const SOURCE_SMS = 'sms';

    public const SOURCE_TYPES = [
        self::SOURCE_WEBSITE,
        self::SOURCE_CHAT,
        self::SOURCE_WALK_IN,
        self::SOURCE_PHONE,
        self::SOURCE_FACEBOOK,
        self::SOURCE_MARKETPLACE,
        self::SOURCE_MANUAL,
        self::SOURCE_REFERRAL,
        self::SOURCE_SERVICE,
        self::SOURCE_SMS,
    ];

    // ── Lifecycle Stage Constants ─────────────────────────────

    public const LIFECYCLE_NEW = 'new';
    public const LIFECYCLE_ACTIVE = 'active';
    public const LIFECYCLE_ENGAGED = 'engaged';
    public const LIFECYCLE_SOLD = 'sold';
    public const LIFECYCLE_LOST = 'lost';
    public const LIFECYCLE_INACTIVE = 'inactive';

    public const LIFECYCLE_STAGES = [
        self::LIFECYCLE_NEW,
        self::LIFECYCLE_ACTIVE,
        self::LIFECYCLE_ENGAGED,
        self::LIFECYCLE_SOLD,
        self::LIFECYCLE_LOST,
        self::LIFECYCLE_INACTIVE,
    ];

    // ── Recorded By Type Constants ────────────────────────────

    public const RECORDED_BY_AI = 'ai_agent';
    public const RECORDED_BY_USER = 'user';
    public const RECORDED_BY_SYSTEM = 'system';

    public const RECORDED_BY_TYPES = [
        self::RECORDED_BY_AI,
        self::RECORDED_BY_USER,
        self::RECORDED_BY_SYSTEM,
    ];

    // ── Intent Constants (from ChatLead) ──────────────────────

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

    // ── Fillable ──────────────────────────────────────────────

    protected $fillable = [
        'tenant_id',
        'prospect_id',
        'name',
        'first_name',
        'last_name',
        'email',
        'phone',
        'source',
        'source_type',
        'source_name',
        'recorded_by_type',
        'recorded_by_id',
        'assigned_to',
        'conversation_id',
        'intent',
        'interested_vehicle_id',
        'vehicle_details',
        'provider_name',
        'external_reference_id',
        'status',
        'lifecycle_stage',
        'notes',
        'last_contacted_at',
        'last_activity_at',
    ];

    protected $casts = [
        'vehicle_details' => 'array',
        'last_contacted_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'new',
        'lifecycle_stage' => 'new',
        'source_type' => 'manual',
        'recorded_by_type' => 'user',
    ];

    // ── Accessors ─────────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        $name = trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
        return $name ?: 'Unknown';
    }

    // ── Relationships ─────────────────────────────────────────

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    public function interestedVehicle(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'interested_vehicle_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_id');
    }

    public function inventoryItems(): BelongsToMany
    {
        return $this->belongsToMany(InventoryItem::class, 'inventory_item_lead');
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(LeadStatusHistory::class)->orderBy('created_at', 'desc');
    }

    public function communications(): HasMany
    {
        return $this->hasMany(LeadCommunication::class);
    }

    // ── Status Transitions ────────────────────────────────────

    /**
     * Transition lead to a new status and record history.
     */
    public function transitionStatus(string $newStatus, ?string $userId = null, ?string $notes = null): self
    {
        $fromStatus = $this->status;

        if ($fromStatus === $newStatus) {
            return $this;
        }

        $this->update([
            'status' => $newStatus,
            'last_activity_at' => now(),
        ]);

        $this->statusHistory()->create([
            'from_status' => $fromStatus,
            'to_status' => $newStatus,
            'changed_by' => $userId ?? auth()->id(),
            'notes' => $notes,
        ]);

        return $this;
    }

    // ── Scopes ────────────────────────────────────────────────

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeBySource(Builder $query, string $sourceType): Builder
    {
        return $query->where('source_type', $sourceType);
    }

    public function scopeAssignedTo(Builder $query, string $userId): Builder
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeUnassigned(Builder $query): Builder
    {
        return $query->whereNull('assigned_to');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', [self::STATUS_SOLD, self::STATUS_LOST, self::STATUS_INACTIVE]);
    }

    public function scopeByLifecycle(Builder $query, string $stage): Builder
    {
        return $query->where('lifecycle_stage', $stage);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $q) use ($term) {
            $q->where('first_name', 'like', "%{$term}%")
              ->orWhere('last_name', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%")
              ->orWhere('phone', 'like', "%{$term}%");
        });
    }
}
