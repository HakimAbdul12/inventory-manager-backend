<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantInvitation extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id',
        'email',
        'name',
        'role_ids',
        'token',
        'invited_by',
        'accepted_at',
        'expires_at',
    ];

    protected $casts = [
        'role_ids' => 'array',
        'accepted_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isAccepted(): bool
    {
        return !is_null($this->accepted_at);
    }
}
