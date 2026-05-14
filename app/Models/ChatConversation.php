<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChatConversation extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $table = 'chat_conversations';

    protected $fillable = [
        'tenant_id',
        'visitor_name',
        'visitor_email',
        'visitor_phone',
        'session_token',
        'state',
        'telegram_chat_id',
        'agent_telegram_chat_id',
        'telegram_notified_agents',
        'metadata',
        'ai_context',
        'last_activity_at',
    ];

    protected $casts = [
        'telegram_notified_agents' => 'array',
        'metadata' => 'array',
        'ai_context' => 'array',
        'last_activity_at' => 'datetime',
    ];

    protected $attributes = [
        'state' => 'ai',
    ];

    public const STATE_AI = 'ai';
    public const STATE_HUMAN = 'human';
    public const STATE_HYBRID = 'hybrid';
    public const STATE_CLOSED = 'closed';

    public const STATES = [
        self::STATE_AI,
        self::STATE_HUMAN,
        self::STATE_HYBRID,
        self::STATE_CLOSED,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $conversation) {
            if (empty($conversation->session_token)) {
                $conversation->session_token = bin2hex(random_bytes(32));
            }
        });
    }

    // ─── Relationships ──────────────────────────────────────

    public function messages(): HasMany
    {
        return $this->hasMany(ChatWidgetMessage::class, 'conversation_id')
            ->orderBy('created_at');
    }

    public function lead(): HasOne
    {
        return $this->hasOne(Lead::class, 'conversation_id');
    }

    // ─── State Machine ──────────────────────────────────────

    public function transitionTo(string $state): self
    {
        if (!in_array($state, self::STATES)) {
            throw new \InvalidArgumentException("Invalid state: {$state}");
        }

        $this->update([
            'state' => $state,
            'last_activity_at' => now(),
        ]);

        return $this;
    }

    public function switchToHuman(string $telegramChatId): self
    {
        $this->update([
            'state' => self::STATE_HUMAN,
            'telegram_chat_id' => $telegramChatId,
            'last_activity_at' => now(),
        ]);

        return $this;
    }

    public function close(): self
    {
        return $this->transitionTo(self::STATE_CLOSED);
    }

    public function resumeAI(): self
    {
        $this->update([
            'state' => self::STATE_AI,
            'agent_telegram_chat_id' => null,
            'last_activity_at' => now(),
        ]);

        return $this;
    }

    public function isActive(): bool
    {
        return $this->state !== self::STATE_CLOSED;
    }

    public function isHumanMode(): bool
    {
        return in_array($this->state, [self::STATE_HUMAN, self::STATE_HYBRID]);
    }

    // ─── Context Management ─────────────────────────────────

    /**
     * Append a message summary to the AI context window.
     */
    public function appendToContext(string $role, string $content): void
    {
        $context = $this->ai_context ?? [];
        $context[] = ['role' => $role, 'content' => $content];

        // Keep last 20 messages for context window
        if (count($context) > 20) {
            $context = array_slice($context, -20);
        }

        $this->update(['ai_context' => $context]);
    }

    // ─── Scopes ─────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('state', '!=', self::STATE_CLOSED);
    }

    public function scopeByState($query, string $state)
    {
        return $query->where('state', $state);
    }

    /**
     * Touch activity timestamp.
     */
    public function touchActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }
}
