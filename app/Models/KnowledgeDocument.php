<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeDocument extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'title',
        'content',
        'doc_type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'doc_type' => 'custom',
        'is_active' => true,
    ];

    public const TYPES = [
        'faq',
        'policy',
        'financing',
        'warranty',
        'delivery',
        'custom',
    ];

    public function chunks(): HasMany
    {
        return $this->hasMany(KnowledgeChunk::class, 'document_id')
            ->orderBy('chunk_index');
    }

    /**
     * Scope to active documents only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
