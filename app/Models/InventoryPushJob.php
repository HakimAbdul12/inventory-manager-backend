<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryPushJob extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'schedule_time',
        'file_format',
        'filters',
        'category_ids',
        'sftp_connection_ids',
        'destination_folder_override',
        'custom_filename',
        'is_active',
        'last_run_at',
        'next_run_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'category_ids' => 'array',
        'sftp_connection_ids' => 'array',
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    // ─── Relationships ──────────────────────────────────────

    public function history(): HasMany
    {
        return $this->hasMany(InventoryPushHistory::class, 'push_job_id');
    }

    // ─── Accessors ──────────────────────────────────────────

    /**
     * Resolve the actual SftpConnection models for this job.
     */
    public function getResolvedConnectionsAttribute()
    {
        $ids = $this->sftp_connection_ids ?? [];
        if (empty($ids)) {
            return collect();
        }

        return SftpConnection::whereIn('id', $ids)->get();
    }

    /**
     * Resolve the actual Category models for this job.
     */
    public function getResolvedCategoriesAttribute()
    {
        $ids = $this->category_ids ?? [];
        if (empty($ids)) {
            return collect();
        }

        return Category::whereIn('id', $ids)->get();
    }

    // ─── Scopes ─────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeScheduled($query)
    {
        return $query->where('type', 'scheduled');
    }

    public function scopeForScheduleTime($query, string $time)
    {
        return $query->where('schedule_time', $time);
    }

    // ─── Helpers ────────────────────────────────────────────

    public function isScheduled(): bool
    {
        return $this->type === 'scheduled';
    }

    /**
     * Calculate the next run time based on schedule_time.
     */
    public function calculateNextRunAt(): ?\Carbon\Carbon
    {
        if (!$this->isScheduled() || !$this->schedule_time) {
            return null;
        }

        [$hour, $minute] = explode(':', $this->schedule_time);
        $next = now()->setTime((int) $hour, (int) $minute, 0);

        if ($next->lte(now())) {
            $next->addDay();
        }

        return $next;
    }
}
