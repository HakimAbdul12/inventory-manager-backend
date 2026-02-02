<?php

namespace App\Models;

use App\Enums\ProcessStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InventoryProcess extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'category_id',
        'user_inputs',
        'custom_prompt',
        'options',
        'current_step',
        'status',
        'inventory_item_id',
        'metadata',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'user_inputs' => 'array',
        'options' => 'array',
        'metadata' => 'array',
        'status' => ProcessStatus::class,
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => ProcessStatus::PENDING,
        'user_inputs' => '{}',
        'options' => '{}',
        'metadata' => '{}',
    ];

    /**
     * Get the category for this process.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the user that owns the process.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the steps for this process.
     */
    public function steps(): HasMany
    {
        return $this->hasMany(ProcessStep::class, 'process_id')->orderBy('step_order');
    }

    /**
     * Get the resulting inventory item.
     */
    public function inventoryItem(): HasOne
    {
        return $this->hasOne(InventoryItem::class, 'process_id');
    }

    /**
     * Get the current step.
     */
    public function getCurrentStepModel(): ?ProcessStep
    {
        return $this->steps()->where('step_name', $this->current_step)->first();
    }

    /**
     * Check if images should be generated.
     */
    public function shouldGenerateImages(): bool
    {
        return $this->options['generateImages'] ?? false;
    }

    /**
     * Get image count option.
     */
    public function getImageCount(): int
    {
        return $this->options['imageCount'] ?? config('inventory.images.default_count', 3);
    }

    /**
     * Get the broadcast channel name.
     */
    public function getBroadcastChannelName(): string
    {
        return "inventory-process.{$this->id}";
    }

    /**
     * Mark process as started.
     */
    public function markAsStarted(): void
    {
        $this->update([
            'status' => ProcessStatus::IN_PROGRESS,
            'started_at' => now(),
        ]);
    }

    /**
     * Mark process as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => ProcessStatus::COMPLETED,
            'completed_at' => now(),
            'metadata' => array_merge($this->metadata ?? [], [
                'total_duration' => $this->started_at?->diffInMilliseconds(now()),
            ]),
        ]);
    }

    /**
     * Mark process as failed.
     */
    public function markAsFailed(string $reason = null): void
    {
        $this->update([
            'status' => ProcessStatus::FAILED,
            'completed_at' => now(),
            'metadata' => array_merge($this->metadata ?? [], [
                'failure_reason' => $reason,
            ]),
        ]);
    }

    /**
     * Advance to next step.
     */
    public function advanceToStep(string $stepName): void
    {
        $this->update(['current_step' => $stepName]);
    }

    /**
     * Calculate total duration in milliseconds.
     */
    public function getTotalDurationAttribute(): ?int
    {
        if (!$this->started_at) {
            return null;
        }

        $endTime = $this->completed_at ?? now();
        return $this->started_at->diffInMilliseconds($endTime);
    }
}
