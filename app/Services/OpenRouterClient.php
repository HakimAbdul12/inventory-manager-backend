<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenRouterClient
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $model;
    protected int $timeout;
    protected int $maxRetries;

    public function __construct()
    {
        $this->baseUrl = config('openrouter.base_url');
        $this->apiKey = config('openrouter.api_key');
        $this->model = config('openrouter.model');
        $this->timeout = config('openrouter.timeout', 60);
        $this->maxRetries = config('openrouter.max_retries', 3);
    }

    /**
     * Create HTTP client with default configuration.
     */
    protected function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->timeout($this->timeout)
            ->withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
                'HTTP-Referer' => config('app.url'),
                'X-Title' => config('app.name'),
            ])
            ->retry($this->maxRetries, 1000);
    }

    /**
     * Send a chat completion request.
     */
    public function chatCompletion(array $messages, array $options = []): array
    {
        $payload = [
            'model' => $options['model'] ?? $this->model,
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? config('openrouter.temperature', 0.7),
            'max_tokens' => $options['max_tokens'] ?? config('openrouter.max_tokens', 2000),
        ];

        // Add response format for JSON if requested
        if ($options['json_mode'] ?? false) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        Log::debug('OpenRouter request', ['model' => $payload['model'], 'messages_count' => count($messages)]);

        $response = $this->client()->post('/chat/completions', $payload);

        if (!$response->successful()) {
            Log::error('OpenRouter API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException("OpenRouter API error: {$response->status()} - {$response->body()}");
        }

        $data = $response->json();

        Log::debug('OpenRouter response', [
            'usage' => $data['usage'] ?? null,
        ]);

        return [
            'content' => $data['choices'][0]['message']['content'] ?? '',
            'usage' => $data['usage'] ?? [],
            'model' => $data['model'] ?? $payload['model'],
            'finish_reason' => $data['choices'][0]['finish_reason'] ?? null,
        ];
    }

    /**
     * Send a simple text prompt and get a response.
     */
    public function prompt(string $prompt, array $options = []): string
    {
        $messages = [
            ['role' => 'user', 'content' => $prompt],
        ];

        if (isset($options['system'])) {
            array_unshift($messages, ['role' => 'system', 'content' => $options['system']]);
        }

        $result = $this->chatCompletion($messages, $options);

        return $result['content'];
    }

    /**
     * Send a structured prompt expecting JSON response.
     */
    public function jsonPrompt(string $prompt, array $options = []): array
    {
        $options['json_mode'] = true;

        $content = $this->prompt($prompt, $options);

        // Try to parse JSON from the response
        $json = $this->extractJson($content);

        if ($json === null) {
            throw new \RuntimeException("Failed to parse JSON from AI response: {$content}");
        }

        return $json;
    }

    /**
     * Extract JSON from a string that might contain additional text.
     */
    protected function extractJson(string $content): ?array
    {
        // 1. Try to extract from markdown code blocks first (most reliable)
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $content, $matches)) {
            $decoded = json_decode($matches[1], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        // 2. Try direct decode of the whole content
        $decoded = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        // 3. Try to find the first valid JSON object or array
        // We use non-greedy matching for the content inside to find the smallest valid block first?
        // Actually, let's just look for the first '{' and the last '}'
        $firstBrace = strpos($content, '{');
        $lastBrace = strrpos($content, '}');

        if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
            $possibleJson = substr($content, $firstBrace, $lastBrace - $firstBrace + 1);
            $decoded = json_decode($possibleJson, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        $firstBracket = strpos($content, '[');
        $lastBracket = strrpos($content, ']');

        if ($firstBracket !== false && $lastBracket !== false && $lastBracket > $firstBracket) {
            $possibleJson = substr($content, $firstBracket, $lastBracket - $firstBracket + 1);
            $decoded = json_decode($possibleJson, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * Test the API connection.
     */
    public function testConnection(): bool
    {
        try {
            $response = $this->prompt('Say "OK" if you can read this.');
            return str_contains(strtolower($response), 'ok');
        } catch (\Exception $e) {
            Log::error('OpenRouter connection test failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get available models.
     */
    public function getModels(): array
    {
        $response = $this->client()->get('/models');

        if (!$response->successful()) {
            throw new \RuntimeException("Failed to fetch models: {$response->status()}");
        }

        return $response->json()['data'] ?? [];
    }

    /**
     * Generate images using the image model (Seedream 4.5).
     * Returns an array of generated images with base64 data.
     */
    public function generateImages(string $prompt, array $options = []): array
    {
        $imageModel = $options['model'] ?? config('openrouter.image_model');
        $imageTimeout = config('openrouter.image_timeout', 120);
        $inputImage = $options['inputImage'] ?? null;

        $messageContent = [['type' => 'text', 'text' => $prompt]];

        if ($inputImage) {
            $messageContent[] = [
                'type' => 'image_url',
                'image_url' => ['url' => $inputImage]
            ];
        }

        $payload = [
            'model' => $imageModel,
            'messages' => [
                [
                    'role' => 'user',
                    // If inputImage is present, use array format. Otherwise use string for compatibility if model expects it (though array is usually fine)
                    'content' => $inputImage ? $messageContent : $prompt
                ],
            ],
        ];

        Log::debug('OpenRouter image generation request', [
            'model' => $imageModel,
            'prompt_length' => strlen($prompt),
        ]);

        $response = Http::baseUrl($this->baseUrl)
            ->timeout($imageTimeout)
            ->withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
                'HTTP-Referer' => config('app.url'),
                'X-Title' => config('app.name'),
            ])
            ->retry(2, 2000)
            ->post('/chat/completions', $payload);

        if (!$response->successful()) {
            Log::error('OpenRouter image generation error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException("OpenRouter image generation error: {$response->status()} - {$response->body()}");
        }

        $data = $response->json();

        Log::debug('OpenRouter image generation response', [
            'model' => $data['model'] ?? $imageModel,
            'provider' => $data['provider'] ?? 'unknown',
        ]);

        // Extract images from response
        $images = [];
        $choices = $data['choices'] ?? [];

        foreach ($choices as $choice) {
            $message = $choice['message'] ?? [];
            $messageImages = $message['images'] ?? [];

            foreach ($messageImages as $img) {
                $imageUrl = $img['image_url']['url'] ?? null;
                if ($imageUrl) {
                    $images[] = [
                        'index' => $img['index'] ?? count($images),
                        'data' => $imageUrl, // This is the base64 data URL
                        'type' => $img['type'] ?? 'image_url',
                    ];
                }
            }
        }

        if (empty($images)) {
            throw new \RuntimeException('No images were generated in the response');
        }

        return $images;
    }

    /**
     * Generate a single image and return as base64.
     */
    public function generateImage(string $prompt, array $options = []): string
    {
        $images = $this->generateImages($prompt, $options);
        return $images[0]['data'] ?? '';
    }
}
