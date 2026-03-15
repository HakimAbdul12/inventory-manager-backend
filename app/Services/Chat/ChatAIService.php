<?php

namespace App\Services\Chat;

use App\Models\ChatConversation;
use App\Models\ChatWidgetMessage;
use App\Models\WorkspaceChatConfig;
use App\Services\OpenRouterClient;


class ChatAIService
{
    protected OpenRouterClient $ai;
    protected InventorySearchService $inventorySearch;
    protected KnowledgeBaseService $knowledgeBase;
    protected LeadCaptureService $leadService;

    public function __construct(
        OpenRouterClient $ai,
        InventorySearchService $inventorySearch,
        KnowledgeBaseService $knowledgeBase,
        LeadCaptureService $leadService
    ) {
        $this->ai = $ai;
        $this->inventorySearch = $inventorySearch;
        $this->knowledgeBase = $knowledgeBase;
        $this->leadService = $leadService;
    }

    /**
     * Process a visitor message and generate an AI response.
     */
    public function processMessage(
        ChatConversation $conversation,
        WorkspaceChatConfig $config,
        string $visitorMessage,
        ?string $attachmentUrl = null,
        ?string $attachmentType = null,
        ?string $attachmentLocalPath = null
    ): array {
        // Transcribe audio if needed
        if ($attachmentType === 'audio' && $attachmentLocalPath) {
            $transcription = $this->transcribeAudio($attachmentLocalPath);
            if ($transcription) {
                $visitorMessage = $visitorMessage 
                    ? "{$visitorMessage}\n\n[Voice Note Transcript]: {$transcription}" 
                    : "[Voice Note Transcript]: {$transcription}";
            }
        }

        $meta = [];
        if ($attachmentUrl) {
            $meta['attachment_url'] = $attachmentUrl;
            $meta['attachment_type'] = $attachmentType;
        }

        // Store visitor message
        $conversation->messages()->create([
            'sender_type' => ChatWidgetMessage::SENDER_VISITOR,
            'content' => $visitorMessage,
            'message_type' => ChatWidgetMessage::TYPE_TEXT,
            'metadata' => empty($meta) ? null : $meta,
        ]);

        $conversation->appendToContext('user', $visitorMessage);
        $conversation->touchActivity();

        // Detect intents before AI call
        $intent = $this->detectIntent($visitorMessage);

        if ($intent === 'human_handoff') {
            return $this->handleHumanHandoffIntent($conversation, $config);
        }

        // Search for relevant inventory if message mentions vehicles
        $vehicleCards = [];
        $imageDescription = null;

        // If an image is attached, pre-identify the vehicle from the image
        if ($attachmentType === 'image' && $attachmentUrl) {
            $imageDescription = $this->identifyVehicleFromImage($attachmentUrl);
            if ($imageDescription) {
                // Enrich the visitor message with image context for better search
                $searchText = $imageDescription;
                $vehicleCards = $this->inventorySearch->searchFromMessage(
                    $searchText,
                    $conversation->tenant_id
                );
            }
        } elseif ($this->isInventoryQuery($visitorMessage)) {
            $vehicleCards = $this->inventorySearch->searchFromMessage(
                $visitorMessage,
                $conversation->tenant_id
            );
        }

        // Retrieve relevant knowledge chunks
        $knowledgeContext = $this->knowledgeBase->retrieveRelevant(
            $visitorMessage,
            $conversation->tenant_id,
            3
        );

        // Build messages for AI
        $messages = $this->buildMessages($conversation, $config, $knowledgeContext, $vehicleCards, $visitorMessage, $attachmentUrl, $attachmentType);

        $tools = [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'capture_lead_info',
                    'description' => 'Capture visitor contact details automatically when they provide their name, email, or phone number. Call this tool immediately when they give their name. Do NOT ask them for more details first.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => [
                                'type' => 'string',
                                'description' => 'The visitor\'s name.',
                            ],
                            'email' => [
                                'type' => 'string',
                                'description' => 'The visitor\'s email address.',
                            ],
                            'phone' => [
                                'type' => 'string',
                                'description' => 'The visitor\'s phone number.',
                            ]
                        ]
                    ]
                ]
            ]
        ];

        // Call AI — use vision model when image is attached
        $aiOptions = [
            'temperature' => $this->getTemperature($config),
            'max_tokens' => 800,
        ];

        if ($attachmentType === 'image' && $attachmentUrl) {
            // Use vision model for image analysis — don't pass tools since
            // free vision models can't handle tool calling properly
            $aiOptions['model'] = config('openrouter.vision_model', 'nvidia/nemotron-nano-12b-v2-vl:free');
        } else {
            $aiOptions['tools'] = $tools;
        }

        $result = $this->ai->chatCompletion($messages, $aiOptions);

        $aiContent = $result['content'];
        $toolCalls = $result['tool_calls'] ?? [];

        if (!empty($toolCalls)) {
            $this->processToolCalls($conversation, $toolCalls);
            if (empty($aiContent)) {
                $aiContent = "Got it, thanks! I've saved your details.";
            }
        }
        $confidenceScore = $this->calculateConfidence($result, $knowledgeContext);

        // Store AI response
        $aiMessage = $conversation->messages()->create([
            'sender_type' => ChatWidgetMessage::SENDER_AI,
            'content' => $aiContent,
            'message_type' => ChatWidgetMessage::TYPE_TEXT,
            'metadata' => [
                'confidence_score' => $confidenceScore,
                'model' => $result['model'] ?? null,
            ],
        ]);

        $conversation->appendToContext('assistant', $aiContent);

        // Build response with optional vehicle cards
        $response = [
            'message' => $aiMessage,
            'confidence_score' => $confidenceScore,
            'vehicle_cards' => $vehicleCards,
            'suggest_human' => $confidenceScore < 0.5 && $config->auto_human_handoff,
        ];

        // Detect lead capture opportunity
        $leadIntent = $this->detectLeadIntent($visitorMessage, $aiContent);
        if ($leadIntent) {
            $response['lead_prompt'] = $leadIntent;
        }

        return $response;
    }

    protected function buildMessages(
        ChatConversation $conversation,
        WorkspaceChatConfig $config,
        array $knowledgeContext,
        array $vehicleCards,
        string $visitorMessage,
        ?string $attachmentUrl = null,
        ?string $attachmentType = null
    ): array {
        $systemPrompt = $this->buildSystemPrompt($config, $knowledgeContext, $vehicleCards, $visitorMessage);

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        // Add conversation history
        $context = $conversation->ai_context ?? [];
        foreach ($context as $entry) {
            $messages[] = [
                'role' => $entry['role'],
                'content' => $entry['content'],
            ];
        }

        // Modify the last user message to include an image for Vision API
        if ($attachmentType === 'image' && $attachmentUrl) {
            $lastIndex = count($messages) - 1;
            if (isset($messages[$lastIndex]) && $messages[$lastIndex]['role'] === 'user') {
                $text = $messages[$lastIndex]['content'];

                // Convert image to base64 data URL so remote AI models can see it
                $imageDataUrl = $this->imageToBase64DataUrl($attachmentUrl);

                $messages[$lastIndex]['content'] = [
                    [
                        'type' => 'text',
                        'text' => $text ?: "The user sent an image. Describe what you see and help them accordingly.",
                    ],
                    [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => $imageDataUrl,
                        ]
                    ]
                ];
            }
        }

        return $messages;
    }

    /**
     * Use the vision model to quickly identify a vehicle from an image.
     * Returns a short text like "BMW M5 sedan" for inventory search.
     */
    protected function identifyVehicleFromImage(string $attachmentUrl): ?string
    {
        try {
            $imageDataUrl = $this->imageToBase64DataUrl($attachmentUrl);
            $visionModel = config('openrouter.vision_model', 'nvidia/nemotron-nano-12b-v2-vl:free');

            $result = $this->ai->chatCompletion([
                ['role' => 'system', 'content' => 'You are a vehicle identification expert. Identify the vehicle in the image and respond with ONLY the make and model (e.g. "BMW M5" or "Toyota Camry sedan"). Nothing else.'],
                ['role' => 'user', 'content' => [
                    ['type' => 'text', 'text' => 'What vehicle is in this image? Reply with only the make and model.'],
                    ['type' => 'image_url', 'image_url' => ['url' => $imageDataUrl]],
                ]],
            ], [
                'model' => $visionModel,
                'max_tokens' => 50,
                'temperature' => 0.1,
            ]);

            $identification = trim($result['content'] ?? '');

            if (!empty($identification) && strlen($identification) < 100) {
                \Illuminate\Support\Facades\Log::info('Vehicle identified from image', ['result' => $identification]);
                return $identification;
            }

            return null;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Vehicle identification from image failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Convert an image URL (or local file path) to a base64 data URL.
     */
    protected function imageToBase64DataUrl(string $url): string
    {
        try {
            // If it's a local URL (e.g. localhost), read from disk directly
            $appUrl = rtrim(config('app.url'), '/');
            if (str_starts_with($url, $appUrl . '/storage/')) {
                $relativePath = str_replace($appUrl . '/storage/', '', $url);
                $fullPath = storage_path('app/public/' . $relativePath);
                if (file_exists($fullPath)) {
                    $mime = mime_content_type($fullPath);
                    $data = base64_encode(file_get_contents($fullPath));
                    return "data:{$mime};base64,{$data}";
                }
            }

            // For remote URLs, download and encode
            $contents = file_get_contents($url);
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->buffer($contents);
            return 'data:' . $mime . ';base64,' . base64_encode($contents);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Could not convert image to base64', ['error' => $e->getMessage()]);
            return $url; // fallback to original URL
        }
    }

    /**
     * Transcribe audio using Groq's free Whisper API.
     * Falls back gracefully if no API key is configured.
     */
    protected function transcribeAudio(string $localPath): ?string
    {
        try {
            $apiKey = config('services.groq.api_key') ?? env('GROQ_API_KEY');
            if (!$apiKey) {
                \Illuminate\Support\Facades\Log::warning('GROQ_API_KEY not set — skipping audio transcription');
                return null;
            }

            if (!file_exists($localPath)) {
                \Illuminate\Support\Facades\Log::warning('Audio file not found for transcription', ['path' => $localPath]);
                return null;
            }

            $response = \Illuminate\Support\Facades\Http::timeout(60)
                ->withHeaders(['Authorization' => 'Bearer ' . $apiKey])
                ->attach('file', file_get_contents($localPath), basename($localPath))
                ->post('https://api.groq.com/openai/v1/audio/transcriptions', [
                    'model' => 'whisper-large-v3-turbo',
                    'response_format' => 'json',
                ]);

            if ($response->successful()) {
                return $response->json('text');
            }

            \Illuminate\Support\Facades\Log::error('Groq Whisper API error', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
            return null;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Whisper transcription exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Build the system prompt with personality, knowledge, and inventory context.
     */
    protected function buildSystemPrompt(
        WorkspaceChatConfig $config,
        array $knowledgeContext,
        array $vehicleCards,
        string $visitorMessage
    ): string {
        $personality = $this->getPersonalityInstructions($config->bot_personality);
        $aggressiveness = $this->getAggressivenessInstructions($config->ai_aggressiveness);

        $prompt = <<<PROMPT
You are "{$config->bot_name}", an AI sales assistant for an automotive dealership.

## Personality
{$personality}

## Sales Approach
{$aggressiveness}

## Rules
- Be helpful, accurate, and concise.
- If unsure, say so honestly and suggest speaking to a real person.
- Never make up vehicle details — only use provided inventory data.
- When showing vehicles, always include the key details: year, make, model, price, mileage.
- When appropriate, suggest actions: "Book a Test Drive", "Request Financing", "Contact Sales".
- Keep responses under 200 words unless the customer asks for detailed information.
- Auto-detect the customer's language and respond in the same language.

## Image Understanding
- If the user sends an image, analyze it carefully.
- Identify the vehicle make, model, body type, color, and any other visible details.
- Use those details to search inventory and suggest similar vehicles.
- If asked "do you have this car?", identify what it is first, then answer based on your inventory data.
PROMPT;

        // Add business hours context
        if (!$config->isWithinBusinessHours()) {
            $prompt .= "\n\n## Business Hours Notice\nThe dealership is currently CLOSED. Let the customer know and offer to collect their contact info for a callback.";
        }

        // Add knowledge base context
        if (!empty($knowledgeContext)) {
            $prompt .= "\n\n## Dealership Knowledge Base\nUse the following information to answer questions:\n";
            foreach ($knowledgeContext as $chunk) {
                $prompt .= "- {$chunk}\n";
            }
        }

        // Add inventory context
        $isInventoryQuery = $this->isInventoryQuery($visitorMessage);
        if (!empty($vehicleCards)) {
            $count = count($vehicleCards);
            $summaryParts = array_map(fn($c) => "{$c['year']} {$c['make']} {$c['model']}", $vehicleCards);
            $summary = implode(', ', $summaryParts);
            $prompt .= "\n\n## Available Inventory\n{$count} matching vehicle(s) found: {$summary}.\nVehicle cards with images, prices, and action buttons will be displayed automatically below your message. Do NOT repeat vehicle details (price, mileage, specs) in your text. Instead, write a brief, conversational intro like \"Here's what I found for you!\" or \"Great news — we have some options that match!\". Keep your text response short (1-2 sentences max) since the cards provide all the details.";
        } elseif ($isInventoryQuery) {
            $prompt .= "\n\n## Inventory Search: No Matches\nNo matching vehicles were found in our current inventory for this specific request. Inform the customer politely. To ensure we don't lose the customer, you MUST encourage them to speak with a human agent who can check upcoming stock or incoming arrivals. Suggest they click the 🙋 button or type \"talk to a human\".";
        }

        return $prompt;
    }

    protected function getPersonalityInstructions(string $personality): string
    {
        return match ($personality) {
            'friendly' => 'Be warm, casual, and approachable. Use emoji occasionally. Make the customer feel like they\'re chatting with a knowledgeable friend.',
            'luxury' => 'Be sophisticated, refined, and exclusive. Use formal language. Make the customer feel they\'re receiving premium, white-glove service.',
            'casual' => 'Be relaxed and informal. Keep it short and punchy. Speak like a helpful neighbor who knows cars.',
            default => 'Be professional, knowledgeable, and courteous. Maintain a polished tone while being approachable.',
        };
    }

    protected function getAggressivenessInstructions(string $level): string
    {
        return match ($level) {
            'informational' => 'Focus on providing information. Do not push for sales. Let the customer lead the conversation.',
            'sales_driven' => 'Proactively suggest vehicles, highlight deals, and create urgency. Encourage booking test drives and asking about financing.',
            default => 'Balance information with gentle sales suggestions. Mention relevant deals when appropriate but don\'t be pushy.',
        };
    }

    protected function getTemperature(WorkspaceChatConfig $config): float
    {
        return match ($config->bot_personality) {
            'friendly', 'casual' => 0.8,
            'luxury' => 0.5,
            default => 0.7,
        };
    }

    /**
     * Detect primary intent from visitor message.
     */
    protected function detectIntent(string $message): ?string
    {
        $lower = strtolower($message);

        $humanKeywords = [
            'real person',
            'human',
            'agent',
            'talk to someone',
            'speak to someone',
            'representative',
            'manager',
            'help me',
            'actual person',
            'live agent',
            'persona real',
            'hablar con alguien',
            'personne réelle',
        ];

        foreach ($humanKeywords as $keyword) {
            if (str_contains($lower, $keyword)) {
                return 'human_handoff';
            }
        }

        return null;
    }

    /**
     * Detect if the message is asking about inventory/vehicles.
     */
    protected function isInventoryQuery(string $message): bool
    {
        $lower = strtolower($message);
        $keywords = [
            'car',
            'vehicle',
            'suv',
            'truck',
            'sedan',
            'coupe',
            'van',
            'price',
            'cost',
            'affordable',
            'budget',
            'cheap',
            'expensive',
            'toyota',
            'honda',
            'ford',
            'bmw',
            'mercedes',
            'audi',
            'tesla',
            'inventory',
            'stock',
            'available',
            'have any',
            'looking for',
            'mileage',
            'year',
            'model',
            'make',
            'used',
            'new',
        ];

        foreach ($keywords as $keyword) {
            if (str_contains($lower, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect lead capture opportunities.
     */
    protected function detectLeadIntent(string $visitorMessage, string $aiResponse): ?string
    {
        $lower = strtolower($visitorMessage);

        if (str_contains($lower, 'test drive') || str_contains($lower, 'drive it')) {
            return 'test_drive';
        }
        if (str_contains($lower, 'financ') || str_contains($lower, 'payment') || str_contains($lower, 'loan')) {
            return 'financing';
        }
        if (str_contains($lower, 'buy') || str_contains($lower, 'purchase') || str_contains($lower, 'want this')) {
            return 'contact_sales';
        }

        return null;
    }

    /**
     * Handle human handoff intent.
     */
    protected function handleHumanHandoffIntent(
        ChatConversation $conversation,
        WorkspaceChatConfig $config
    ): array {
        $systemMessage = $conversation->messages()->create([
            'sender_type' => ChatWidgetMessage::SENDER_AI,
            'content' => "I'll connect you with a real person right away. Please hold on a moment while I notify our team! 🙋",
            'message_type' => ChatWidgetMessage::TYPE_SYSTEM,
        ]);

        return [
            'message' => $systemMessage,
            'confidence_score' => 1.0,
            'vehicle_cards' => [],
            'suggest_human' => false,
            'request_human_handoff' => true,
        ];
    }

    /**
     * Calculate a confidence score (0-1) for the AI response.
     */
    protected function calculateConfidence(array $result, array $knowledgeContext): float
    {
        $score = 0.6; // Base

        // Higher if knowledge context was available
        if (!empty($knowledgeContext)) {
            $score += 0.2;
        }

        // Higher if we got a complete response
        if (($result['finish_reason'] ?? '') === 'stop') {
            $score += 0.1;
        }

        // Lower if response is very short (potential hallucination)
        $contentLength = strlen($result['content'] ?? '');
        if ($contentLength < 20) {
            $score -= 0.2;
        }

        return max(0.0, min(1.0, $score));
    }

    /**
     * Process any tool calls returned by the AI.
     */
    protected function processToolCalls(ChatConversation $conversation, array $toolCalls): void
    {
        foreach ($toolCalls as $call) {
            if ($call['type'] === 'function' && $call['function']['name'] === 'capture_lead_info') {
                $args = json_decode($call['function']['arguments'], true);
                if ($args) {
                    $this->leadService->captureLead($conversation, $args);

                    // Also update the conversation's visitor name directly for immediate UI updates
                    if (!empty($args['name']) && empty($conversation->visitor_name)) {
                        $conversation->update(['visitor_name' => $args['name']]);
                    }
                }
            }
        }
    }
}
