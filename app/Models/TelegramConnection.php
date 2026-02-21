<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramConnection extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'telegram_chat_id',
        'connected_by',
        'is_active',
        'auto_away_message',
        'agent_sla_minutes',
        'verified_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'verified_at' => 'datetime',
    ];

    protected $attributes = [
        'is_active' => false,
        'agent_sla_minutes' => 5,
    ];

    public function connectedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'connected_by');
    }

    /**
     * Check if the connection is verified and active.
     */
    public function isReady(): bool
    {
        return $this->is_active
            && $this->verified_at !== null
            && !empty($this->telegram_chat_id);
    }

    /**
     * Mark connection as verified.
     */
    public function markVerified(): self
    {
        $this->update([
            'verified_at' => now(),
            'is_active' => true,
        ]);

        return $this;
    }
}
