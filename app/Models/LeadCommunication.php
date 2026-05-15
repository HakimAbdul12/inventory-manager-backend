<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadCommunication extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    public const CHANNEL_PHONE = 'phone';
    public const CHANNEL_EMAIL = 'email';
    public const CHANNEL_SMS = 'sms';

    public const CHANNELS = [
        self::CHANNEL_PHONE,
        self::CHANNEL_EMAIL,
        self::CHANNEL_SMS,
    ];

    public const DIRECTION_OUTBOUND = 'outbound';
    public const DIRECTION_INBOUND = 'inbound';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_FAILED = 'failed';
    public const STATUS_LOGGED = 'logged';

    public const PHONE_DISPOSITIONS = [
        'answered',
        'voicemail',
        'no_answer',
        'busy',
        'wrong_number',
        'left_message',
    ];

    protected $fillable = [
        'tenant_id',
        'lead_id',
        'channel',
        'direction',
        'from_address',
        'to_address',
        'subject',
        'body',
        'body_html',
        'status',
        'metadata',
        'sent_by',
        'sent_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function sentByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    // ── Scopes ───────────────────────────────────────────────

    public function scopeByChannel(Builder $query, string $channel): Builder
    {
        return $query->where('channel', $channel);
    }

    public function scopeByLead(Builder $query, string $leadId): Builder
    {
        return $query->where('lead_id', $leadId);
    }
}
