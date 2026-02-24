<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryPushHistory extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'inventory_push_history';

    protected $fillable = [
        'tenant_id',
        'push_job_id',
        'triggered_by',
        'file_format',
        'total_records',
        'status',
        'error_message',
        'started_at',
        'completed_at',
        'execution_time_ms',
        'file_name',
        'target_connections',
        'connection_results',
    ];

    protected $casts = [
        'total_records' => 'integer',
        'execution_time_ms' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'target_connections' => 'array',
        'connection_results' => 'array',
    ];

    // ─── Relationships ──────────────────────────────────────

    public function pushJob(): BelongsTo
    {
        return $this->belongsTo(InventoryPushJob::class, 'push_job_id');
    }

    // ─── Scopes ─────────────────────────────────────────────

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    // ─── Helpers ────────────────────────────────────────────

    public function markRunning(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    public function markCompleted(string $status, int $totalRecords, ?string $error = null, ?array $connectionResults = null): void
    {
        $startedAt = $this->started_at ?? now();
        $this->update([
            'status' => $status,
            'total_records' => $totalRecords,
            'completed_at' => now(),
            'execution_time_ms' => (int) $startedAt->diffInMilliseconds(now()),
            'error_message' => $error,
            'connection_results' => $connectionResults,
        ]);
    }
}
