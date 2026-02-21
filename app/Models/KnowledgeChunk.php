<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeChunk extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $table = 'knowledge_chunks';

    public $timestamps = false;

    protected $fillable = [
        'document_id',
        'tenant_id',
        'chunk_text',
        'embedding',
        'chunk_index',
        'token_count',
        'created_at',
    ];

    protected $casts = [
        'embedding' => 'array',
        'chunk_index' => 'integer',
        'token_count' => 'integer',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $chunk) {
            if (empty($chunk->created_at)) {
                $chunk->created_at = now();
            }
        });
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(KnowledgeDocument::class, 'document_id');
    }

    /**
     * Calculate cosine similarity between this chunk's embedding and a query vector.
     */
    public function cosineSimilarity(array $queryVector): float
    {
        $embedding = $this->embedding;
        if (!$embedding || count($embedding) !== count($queryVector)) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < count($embedding); $i++) {
            $dotProduct += $embedding[$i] * $queryVector[$i];
            $normA += $embedding[$i] ** 2;
            $normB += $queryVector[$i] ** 2;
        }

        $normA = sqrt($normA);
        $normB = sqrt($normB);

        if ($normA == 0 || $normB == 0) {
            return 0.0;
        }

        return $dotProduct / ($normA * $normB);
    }
}
