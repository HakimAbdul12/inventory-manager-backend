<?php

namespace App\Services\Chat;

use App\Models\KnowledgeChunk;
use App\Models\KnowledgeDocument;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class KnowledgeBaseService
{
    protected string $embeddingModel;
    protected ?string $embeddingApiKey;

    public function __construct()
    {
        $this->embeddingModel = config('services.openai.embedding_model', 'text-embedding-3-small');
        $this->embeddingApiKey = config('services.openai.api_key') ?? '';
    }

    /**
     * Process a document: chunk it and generate embeddings.
     */
    public function processDocument(KnowledgeDocument $document): void
    {
        // Delete existing chunks
        $document->chunks()->delete();

        // Chunk the content
        $chunks = $this->chunkText($document->content);

        foreach ($chunks as $index => $chunk) {
            $embedding = $this->generateEmbedding($chunk['text']);

            KnowledgeChunk::withoutGlobalScope('tenant')->create([
                'document_id' => $document->id,
                'tenant_id' => $document->tenant_id,
                'chunk_text' => $chunk['text'],
                'embedding' => $embedding,
                'chunk_index' => $index,
                'token_count' => $chunk['token_count'],
            ]);
        }

        Log::info('Knowledge document processed', [
            'document_id' => $document->id,
            'chunks_created' => count($chunks),
        ]);
    }

    /**
     * Retrieve the top-K most relevant chunks for a query.
     */
    public function retrieveRelevant(string $query, string $tenantId, int $topK = 3): array
    {
        $queryEmbedding = $this->generateEmbedding($query);

        if (empty($queryEmbedding)) {
            return [];
        }

        // Get all chunks for this tenant (for MVP — at scale, use vector DB)
        $chunks = KnowledgeChunk::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->whereNotNull('embedding')
            ->get();

        if ($chunks->isEmpty()) {
            return [];
        }

        // Calculate similarities and rank
        $scored = $chunks->map(function (KnowledgeChunk $chunk) use ($queryEmbedding) {
            return [
                'text' => $chunk->chunk_text,
                'score' => $chunk->cosineSimilarity($queryEmbedding),
            ];
        })
            ->sortByDesc('score')
            ->take($topK)
            ->filter(fn($item) => $item['score'] > 0.3) // Minimum relevance threshold
            ->values();

        return $scored->pluck('text')->toArray();
    }

    /**
     * Chunk text into segments of ~500 tokens with overlap.
     */
    public function chunkText(string $text, int $maxTokens = 500, int $overlap = 50): array
    {
        $sentences = $this->splitIntoSentences($text);
        $chunks = [];
        $currentChunk = '';
        $currentTokens = 0;

        foreach ($sentences as $sentence) {
            $sentenceTokens = $this->estimateTokens($sentence);

            if ($currentTokens + $sentenceTokens > $maxTokens && !empty($currentChunk)) {
                $chunks[] = [
                    'text' => trim($currentChunk),
                    'token_count' => $currentTokens,
                ];

                // Keep overlap by starting new chunk with the last portion
                $overlapText = $this->getOverlapText($currentChunk, $overlap);
                $currentChunk = $overlapText . ' ' . $sentence;
                $currentTokens = $this->estimateTokens($currentChunk);
            } else {
                $currentChunk .= ' ' . $sentence;
                $currentTokens += $sentenceTokens;
            }
        }

        // Don't forget the last chunk
        if (!empty(trim($currentChunk))) {
            $chunks[] = [
                'text' => trim($currentChunk),
                'token_count' => $currentTokens,
            ];
        }

        return $chunks;
    }

    /**
     * Generate an embedding vector for text using OpenAI API.
     */
    public function generateEmbedding(string $text): array
    {
        if (empty($this->embeddingApiKey)) {
            Log::warning('OpenAI API key not configured for embeddings');
            return [];
        }

        // Cache embeddings to avoid redundant API calls
        $cacheKey = 'embedding:' . md5($text);
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->embeddingApiKey}",
                'Content-Type' => 'application/json',
            ])
                ->timeout(30)
                ->post('https://api.openai.com/v1/embeddings', [
                    'model' => $this->embeddingModel,
                    'input' => $text,
                ]);

            if (!$response->successful()) {
                Log::error('OpenAI Embedding API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [];
            }

            $embedding = $response->json('data.0.embedding', []);

            // Cache for 24 hours
            Cache::put($cacheKey, $embedding, now()->addHours(24));

            return $embedding;
        } catch (\Exception $e) {
            Log::error('Embedding generation failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Split text into sentences.
     */
    protected function splitIntoSentences(string $text): array
    {
        // Split on common sentence terminators
        $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        // Also split on double newlines (paragraph boundaries)
        $result = [];
        foreach ($sentences as $sentence) {
            $parts = preg_split('/\n{2,}/', $sentence, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($parts as $part) {
                $trimmed = trim($part);
                if (!empty($trimmed)) {
                    $result[] = $trimmed;
                }
            }
        }

        return $result;
    }

    /**
     * Rough token estimation (~4 chars per token for English).
     */
    protected function estimateTokens(string $text): int
    {
        return (int) ceil(strlen($text) / 4);
    }

    /**
     * Get the last N tokens of text for chunk overlap.
     */
    protected function getOverlapText(string $text, int $overlapTokens): string
    {
        $words = explode(' ', $text);
        $overlapWords = (int) ceil($overlapTokens * 0.75); // ~0.75 words per token
        $start = max(0, count($words) - $overlapWords);

        return implode(' ', array_slice($words, $start));
    }
}
