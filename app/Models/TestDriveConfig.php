<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestDriveConfig extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'max_per_day',
        'start_time',
        'end_time',
        'duration_minutes',
        'buffer_minutes',
        'max_concurrent',
        'available_days',
        'blocked_dates',
        'is_active',
    ];

    protected $casts = [
        'available_days' => 'array',
        'blocked_dates' => 'array',
        'is_active' => 'boolean',
        'max_per_day' => 'integer',
        'duration_minutes' => 'integer',
        'buffer_minutes' => 'integer',
        'max_concurrent' => 'integer',
    ];

    protected $attributes = [
        'start_time' => '09:00',
        'end_time' => '17:00',
        'duration_minutes' => 15,
        'buffer_minutes' => 0,
        'max_concurrent' => 1,
        'is_active' => false,
    ];

    /**
     * Check if a specific day-of-week is available.
     * Day is 0=Sunday, 1=Monday, ... 6=Saturday.
     */
    public function isDayAvailable(int $dayOfWeek): bool
    {
        $days = $this->available_days ?? [1, 2, 3, 4, 5]; // Default Mon-Fri
        return in_array($dayOfWeek, $days);
    }

    /**
     * Check if a specific date is blocked.
     */
    public function isDateBlocked(string $date): bool
    {
        $blocked = $this->blocked_dates ?? [];
        return in_array($date, $blocked);
    }
}
