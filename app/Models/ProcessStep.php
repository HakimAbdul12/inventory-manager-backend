<?php

namespace App\Models;

use App\Enums\StepStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessStep extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'process_id',
        'step_name',
        'step_order',
        'status',
        'started_at',
        'completed_at',
        'result',
        'logs',
        'error',
        'retry_count',
    ];

    protected $casts = [
        'status' => StepStatus::class,
        'result' => 'array',
        'logs' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'retry_count' => 'integer',
        'step_order' => 'integer',
    ];

    protected $attributes = [
        'status' => StepStatus::PENDING,
        'logs' => '[]',
        'retry_count' => 0,
    ];

    /**
     * Get the process that owns the step.
     */
    public function process(): BelongsTo
    {
        return $this->belongsTo(InventoryProcess::class, 'process_id');
    }

    /**
     * Mark step as in progress.
     */
    public function markAsInProgress(): void
    {
        $this->update([
            'status' => StepStatus::IN_PROGRESS,
            'started_at' => now(),
        ]);

        $this->addLog('info', 'Step started');
    }

    /**
     * Mark step as completed.
     */
    public function markAsCompleted(array $result = null): void
    {
        $this->update([
            'status' => StepStatus::COMPLETED,
            'completed_at' => now(),
            'result' => $result,
        ]);

        $this->addLog('success', 'Step completed successfully');
    }

    /**
     * Mark step as failed.
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => StepStatus::FAILED,
            'completed_at' => now(),
            'error' => $error,
        ]);

        $this->addLog('error', "Step failed: {$error}");
    }

    /**
     * Mark step as skipped.
     */
    public function markAsSkipped(string $reason = null): void
    {
        $this->update([
            'status' => StepStatus::SKIPPED,
            'completed_at' => now(),
        ]);

        $this->addLog('info', $reason ?? 'Step skipped');
    }

    /**
     * Increment retry count.
     */
    public function incrementRetry(): void
    {
        $this->increment('retry_count');
        $this->addLog('warning', "Retry attempt #{$this->retry_count}");
    }

    /**
     * Add a log entry.
     */
    public function addLog(string $level, string $message): void
    {
        $logs = $this->logs ?? [];
        $logs[] = [
            'timestamp' => now()->toIso8601String(),
            'level' => $level,
            'message' => $message,
        ];

        $this->update(['logs' => $logs]);
    }

    /**
     * Get duration in milliseconds.
     */
    public function getDurationAttribute(): ?int
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        return $this->started_at->diffInMilliseconds($this->completed_at);
    }

    /**
     * Get step configuration from config.
     */
    public function getConfigAttribute(): array
    {
        return config("inventory.steps.{$this->step_name}", []);
    }

    /**
     * Check if step can be retried.
     */
    public function canRetry(): bool
    {
        $maxRetries = $this->config['retries'] ?? 3;
        return $this->retry_count < $maxRetries;
    }
}
