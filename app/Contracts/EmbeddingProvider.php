<?php

namespace App\Contracts;

interface EmbeddingProvider
{
    /**
     * Generate an embedding vector for the given text.
     *
     * @return float[] The embedding vector
     */
    public function embed(string $text): array;

    /**
     * Generate embeddings for multiple texts in batch.
     *
     * @param  string[]  $texts
     * @return float[][] Array of embedding vectors
     */
    public function embedBatch(array $texts): array;
}
