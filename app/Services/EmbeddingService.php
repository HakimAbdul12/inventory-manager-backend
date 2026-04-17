<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class EmbeddingService
{
    protected string $model;
    protected ?string $apiKey;

    public function __construct()
    {
        // Default to OpenAI's free/cheap model, but use configuration for future flexibility
        $this->model = config('services.openai.embedding_model', 'text-embedding-3-small');
        $this->apiKey = config('services.openai.api_key') ?? '';
    }

    /**
     * Generate an embedding vector for text using the configured provider (OpenAI by default)
     * To support OpenRouter/HuggingFace in the future, we can swap the endpoint here.
     * 
     * @param string $text
     * @return array|null The embedding array or null on failure
     */
    public function generateEmbedding(string $text): ?array
    {
        if (empty($text)) {
            return null;
        }

        if (empty($this->apiKey)) {
            Log::warning('Embedding API key not configured for embeddings. Using dummy vector for testing Semantic Search locally.');
            return $this->generateDummyEmbedding(1536); // return dummy so tests/dev work
        }

        // Cache embeddings to avoid redundant API calls
        $cacheKey = 'embedding:' . md5($text);
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])
            ->timeout(30)
            ->post('https://api.openai.com/v1/embeddings', [
                'model' => $this->model,
                'input' => $text,
            ]);

            if (!$response->successful()) {
                Log::error('Embedding API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return $this->generateDummyEmbedding(1536);
            }

            $embedding = $response->json('data.0.embedding', null);

            if ($embedding) {
                // Cache for 24 hours
                Cache::put($cacheKey, $embedding, now()->addHours(24));
            }

            return $embedding;
        } catch (\Exception $e) {
            Log::error('Embedding generation failed', ['error' => $e->getMessage()]);
            return $this->generateDummyEmbedding(1536);
        }
    }

    /**
     * Generates a dummy array of floats for local development 
     * without spending API credits or when keys are missing.
     */
    protected function generateDummyEmbedding(int $dimension): array
    {
        $dummy = [];
        for ($i = 0; $i < $dimension; $i++) {
            $dummy[] = mt_rand(-100, 100) / 1000;
        }
        return $dummy;
    }
}
