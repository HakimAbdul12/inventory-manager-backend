<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatWidgetMessage extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'chat_widget_messages';

    public $timestamps = false;

    protected $fillable = [
        'conversation_id',
        'sender_type',
        'content',
        'message_type',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    protected $attributes = [
        'message_type' => 'text',
    ];

    public const SENDER_VISITOR = 'visitor';
    public const SENDER_AI = 'ai';
    public const SENDER_HUMAN = 'human_agent';

    public const TYPE_TEXT = 'text';
    public const TYPE_VEHICLE_CARD = 'vehicle_card';
    public const TYPE_CTA_BUTTON = 'cta_button';
    public const TYPE_LEAD_FORM = 'lead_form';
    public const TYPE_SYSTEM = 'system';

    protected static function booted(): void
    {
        static::creating(function (self $message) {
            if (empty($message->created_at)) {
                $message->created_at = now();
            }
        });
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    /**
     * Check if this is a message from the AI.
     */
    public function isFromAI(): bool
    {
        return $this->sender_type === self::SENDER_AI;
    }

    /**
     * Get confidence score from metadata (AI messages only).
     */
    public function getConfidenceScore(): ?float
    {
        return $this->metadata['confidence_score'] ?? null;
    }
}
