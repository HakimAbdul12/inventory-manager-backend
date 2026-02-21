<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatAnalytic extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $table = 'chat_analytics';

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'date',
        'total_conversations',
        'total_messages',
        'human_handoff_count',
        'leads_captured',
        'avg_confidence_score',
        'most_requested_vehicles',
        'avg_response_time_seconds',
        'created_at',
    ];

    protected $casts = [
        'date' => 'date',
        'most_requested_vehicles' => 'array',
        'avg_confidence_score' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $analytic) {
            if (empty($analytic->created_at)) {
                $analytic->created_at = now();
            }
        });
    }

    /**
     * Get or create the analytics record for today.
     */
    public static function forToday(string $tenantId): self
    {
        return self::withoutGlobalScope('tenant')->firstOrCreate(
            ['tenant_id' => $tenantId, 'date' => now()->toDateString()],
            [
                'total_conversations' => 0,
                'total_messages' => 0,
                'human_handoff_count' => 0,
                'leads_captured' => 0,
                'avg_confidence_score' => 0,
                'most_requested_vehicles' => [],
                'avg_response_time_seconds' => 0,
            ]
        );
    }

    /**
     * Increment a specific counter.
     */
    public function incrementStat(string $field, int $amount = 1): void
    {
        $this->increment($field, $amount);
    }
}
