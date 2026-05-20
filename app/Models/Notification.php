<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notification extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id',
        'sender_id',
        'type',
        'title',
        'body',
        'action_url',
        'category',
        'data',
        'subject_type',
        'subject_id',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    // ── Relationships ────────────────────────────────────────

    public function recipients(): HasMany
    {
        return $this->hasMany(NotificationRecipient::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'notification_recipients');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
