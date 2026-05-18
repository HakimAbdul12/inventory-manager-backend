<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notification extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'type',
        'title',
        'body',
        'action_url',
        'subject_type',
        'subject_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    // ── Scopes ───────────────────────────────────────────────

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // ── Helpers ──────────────────────────────────────────────

    public function markAsRead(): void
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Send a notification to a user.
     */
    public static function send(
        int $userId,
        string $type,
        string $title,
        ?string $body = null,
        ?string $actionUrl = null,
        ?Model $subject = null,
        ?string $tenantId = null,
    ): self {
        $tenantId = $tenantId ?? (app()->bound('current_tenant') ? app('current_tenant')->id : null);

        return static::create([
            'tenant_id'    => $tenantId,
            'user_id'      => $userId,
            'type'         => $type,
            'title'        => $title,
            'body'         => $body,
            'action_url'   => $actionUrl,
            'subject_type' => $subject ? $subject->getMorphClass() : null,
            'subject_id'   => $subject ? $subject->getKey() : null,
        ]);
    }

    // ── Serialization ───────────────────────────────────────

    protected $appends = ['message'];

    public function getMessageAttribute(): ?string
    {
        return $this->body;
    }
}

