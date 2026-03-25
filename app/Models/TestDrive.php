<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TestDrive extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_NO_SHOW = 'no_show';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
        self::STATUS_NO_SHOW,
    ];

    protected $fillable = [
        'tenant_id',
        'booking_code',
        'conversation_id',
        'vehicle_id',
        'visitor_name',
        'visitor_email',
        'visitor_phone',
        'scheduled_date',
        'scheduled_time',
        'end_time',
        'status',
        'notes',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'cancelled_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $testDrive) {
            if (empty($testDrive->booking_code)) {
                $testDrive->booking_code = self::generateBookingCode();
            }
        });
    }

    /**
     * Generate a unique 6-digit alphanumeric booking code.
     */
    public static function generateBookingCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
            // Ensure it's alphanumeric and avoid ambiguous chars
            $code = preg_replace('/[0OIL1]/', '', $code . Str::random(3));
            $code = substr($code, 0, 6);
        } while (self::where('booking_code', $code)->exists());

        return $code;
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'vehicle_id');
    }

    // ── Scopes ───────────────────────────────────────────

    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_date', '>=', now()->toDateString())
            ->whereNotIn('status', [self::STATUS_CANCELLED, self::STATUS_COMPLETED]);
    }

    public function scopeForDate($query, string $date)
    {
        return $query->where('scheduled_date', $date);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_CANCELLED]);
    }

    /**
     * Check if the test drive can be cancelled/rescheduled.
     */
    public function isModifiable(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED]);
    }
}
