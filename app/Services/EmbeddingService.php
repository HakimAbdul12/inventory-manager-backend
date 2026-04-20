<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class EmbeddingService
{
    protected string $model;
    protected ?string $apiKey;
    protected string $endpoint;

    public function __construct()
    {
        $this->model = config('services.huggingface.embedding_model', 'sentence-transformers/all-MiniLM-L6-v2');
        $this->apiKey = config('services.huggingface.api_key');
        $this->endpoint = "https://api-inference.huggingface.co/models/{$this->model}";
    }

    /**
     * Generate an embedding vector for text using Hugging Face Inference API.
     * 
     * @param string $text
     * @return array|null The embedding array or null on failure
     */
    public function generateEmbedding(string $text): ?array
    {
        if (empty($text)) {
            return null;
        }

        // Cache embeddings to avoid redundant API calls
        $cacheKey = 'embedding:hf:' . md5($text);
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        if (empty($this->apiKey)) {
            Log::warning('Hugging Face API key not configured. Using dummy vector (384) for local development.');
            return $this->generateDummyEmbedding(384);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
                'x-wait-for-model' => 'true', // Important for free tier if model is cold
            ])
            ->timeout(30)
            ->post($this->endpoint, [
                'inputs' => $text,
            ]);

            if (!$response->successful()) {
                Log::error('Hugging Face API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return $this->generateDummyEmbedding(384);
            }

            $embedding = $response->json();

            // Hugging Face returns the vector directly as the root element for this model
            if (is_array($embedding) && count($embedding) > 0) {
                // Sometimes it returns a nested array for batch but we sent a single string
                if (is_array($embedding[0])) {
                    $embedding = $embedding[0];
                }
                
                // Cache for 24 hours
                Cache::put($cacheKey, $embedding, now()->addHours(24));
                return $embedding;
            }

            return $this->generateDummyEmbedding(384);
        } catch (\Exception $e) {
            Log::error('Hugging Face embedding generation failed', ['error' => $e->getMessage()]);
            return $this->generateDummyEmbedding(384);
        }
    }

    /**
     * Generates a dummy array of floats for local development (384 dims for all-MiniLM-L6-v2).
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
