<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PublishingBatch extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'status',
        'total_items',
        'successful_items',
        'failed_items',
        'metadata',
    ];

    protected $casts = [
        'total_items' => 'integer',
        'successful_items' => 'integer',
        'failed_items' => 'integer',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PublishingBatchItem::class, 'batch_id');
    }

    public function updateProgress(): void
    {
        $total = $this->items()->count();
        $successful = $this->items()->where('status', 'published')->count();
        $failed = $this->items()->where('status', 'failed')->count();
        $inProgress = $this->items()->whereIn('status', ['pending', 'in_progress'])->count();

        $status = 'in_progress';
        if ($inProgress === 0) {
            $status = ($failed > 0 && $successful === 0) ? 'failed' : 'completed';
        }

        $this->update([
            'total_items' => $total,
            'successful_items' => $successful,
            'failed_items' => $failed,
            'status' => $status,
        ]);
    }
}
