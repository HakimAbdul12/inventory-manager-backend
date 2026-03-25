<?php

namespace App\Services\Chat;

use App\Events\NewTestDriveBooked;
use App\Models\ChatConversation;
use App\Models\ChatWidgetMessage;
use App\Models\TestDriveConfig;
use App\Models\WorkspaceChatConfig;
use App\Services\OpenRouterClient;


class ChatAIService
{
    protected OpenRouterClient $ai;
    protected InventorySearchService $inventorySearch;
    protected KnowledgeBaseService $knowledgeBase;
    protected LeadCaptureService $leadService;
    protected TestDriveService $testDriveService;

    public function __construct(
        OpenRouterClient $ai,
        InventorySearchService $inventorySearch,
        KnowledgeBaseService $knowledgeBase,
        LeadCaptureService $leadService,
        TestDriveService $testDriveService
    ) {
        $this->ai = $ai;
        $this->inventorySearch = $inventorySearch;
        $this->knowledgeBase = $knowledgeBase;
        $this->leadService = $leadService;
        $this->testDriveService = $testDriveService;
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

        // Pre-identify vehicle from image (separate vision call)
        $imageDescription = null;
        if ($attachmentType === 'image' && $attachmentUrl) {
            $imageDescription = $this->identifyVehicleFromImage($attachmentUrl);
        }

        // Retrieve relevant knowledge chunks
        $knowledgeContext = $this->knowledgeBase->retrieveRelevant(
            $visitorMessage,
            $conversation->tenant_id,
            3
        );

        // Build messages for AI (no pre-loaded inventory)
        $messages = $this->buildMessages(
            $conversation, $config, $knowledgeContext,
            $visitorMessage, $imageDescription,
            $attachmentUrl, $attachmentType
        );

        // AI call options
        // NOTE: Reasoning models (like nemotron) use hidden reasoning tokens that
        // count against max_tokens. 4096 ensures enough room for both reasoning
        // and visible content output.
        $aiOptions = [
            'temperature' => $this->getTemperature($config),
            'max_tokens' => 4096,
        ];

        if ($attachmentType === 'image' && $attachmentUrl) {
            // Use vision model for image analysis — don't pass tools since
            // free vision models can't handle tool calling properly
            $aiOptions['model'] = config('openrouter.vision_model', 'nvidia/nemotron-nano-12b-v2-vl:free');
        } else {
            $aiOptions['tools'] = $this->getToolDefinitions();
        }

        // ─── Tool Loop ──────────────────────────────────────────────
        // The AI decides what tools to call. We execute them, send
        // results back, and let the AI compose the final response.
        $result = $this->ai->chatCompletion($messages, $aiOptions);

        $vehicleCards = [];
        $maxIterations = 3;
        $iteration = 0;

        while (!empty($result['tool_calls']) && $iteration < $maxIterations) {
            $iteration++;

            // Append assistant message (with tool_calls) to conversation
            $messages[] = [
                'role' => 'assistant',
                'content' => $result['content'] ?? null,
                'tool_calls' => $result['tool_calls'],
            ];

            // Execute each tool and append results
            foreach ($result['tool_calls'] as $toolCall) {
                if (($toolCall['type'] ?? 'function') !== 'function') {
                    continue;
                }

                $toolResult = $this->executeTool($conversation, $toolCall);

                // Capture vehicle cards from inventory search
                $fnName = $toolCall['function']['name'] ?? '';
                if ($fnName === 'search_inventory' && !empty($toolResult['vehicles'])) {
                    $vehicleCards = $toolResult['vehicles'];
                }

                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $toolCall['id'],
                    'content' => json_encode($toolResult),
                ];
            }

            // Follow-up AI call with tool results
            $result = $this->ai->chatCompletion($messages, $aiOptions);
        }

        // ─── Extract Content ────────────────────────────────────────
        $aiContent = $result['content'] ?? '';

        // Reasoning models may wrap visible content after <think>...</think> tags.
        if (str_contains($aiContent, '</think>')) {
            $aiContent = trim(preg_replace('/<think>[\s\S]*?<\/think>/i', '', $aiContent));
        }

        // Fallback: If AI returned empty content (reasoning token exhaustion)
        if (empty(trim($aiContent))) {
            \Illuminate\Support\Facades\Log::warning('AI returned empty content — reasoning token exhaustion likely', [
                'usage' => $result['usage'] ?? [],
                'finish_reason' => $result['finish_reason'] ?? 'unknown',
            ]);

            if (!empty($vehicleCards)) {
                $count = count($vehicleCards);
                $aiContent = "I found {$count} vehicle(s) that might interest you! Take a look at the options below and let me know if any catch your eye — I'm happy to share more details or arrange a test drive.";
            } else {
                $aiContent = "I'd love to help! Could you tell me a bit more about what you're looking for — perhaps the type of vehicle, your budget, or any must-have features? That way I can find the best options for you.";
            }
        }

        // ─── Response ───────────────────────────────────────────────
        $confidenceScore = $this->calculateConfidence($result, $knowledgeContext);

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

        $response = [
            'message' => $aiMessage,
            'confidence_score' => $confidenceScore,
            'vehicle_cards' => $vehicleCards,
            'suggest_human' => !empty($aiContent) && $confidenceScore < 0.5 && $config->auto_human_handoff,
        ];

        // Detect lead capture opportunity
        $leadIntent = $this->detectLeadIntent($visitorMessage, $aiContent);
        if ($leadIntent) {
            $response['lead_prompt'] = $leadIntent;
        }

        return $response;
    }

    // ─── Message Building ───────────────────────────────────────────

    protected function buildMessages(
        ChatConversation $conversation,
        WorkspaceChatConfig $config,
        array $knowledgeContext,
        string $visitorMessage,
        ?string $imageDescription = null,
        ?string $attachmentUrl = null,
        ?string $attachmentType = null
    ): array {
        $systemPrompt = $this->buildSystemPrompt($config, $knowledgeContext, $conversation);

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

        // If we identified a vehicle from an image, inject a hint for the AI
        if ($imageDescription && !($attachmentType === 'image' && $attachmentUrl)) {
            // This case shouldn't normally happen, but handle gracefully
            $messages[] = [
                'role' => 'system',
                'content' => "The customer sent an image. Vehicle identified: {$imageDescription}. Use search_inventory to find matching vehicles.",
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
     * Build the system prompt with personality, knowledge, and tool guidance.
     */
    protected function buildSystemPrompt(
        WorkspaceChatConfig $config,
        array $knowledgeContext,
        ChatConversation $conversation
    ): string {
        $personality = $this->getPersonalityInstructions($config->bot_personality);
        $aggressiveness = $this->getAggressivenessInstructions($config->ai_aggressiveness);

        $currentDate = now()->format('l, F j, Y g:i A');

        $prompt = <<<PROMPT
## Core Identity
You are an expert, friendly automotive assistant for {$config->dealership_name}.
Current Date and Time: {$currentDate}
Your primary goal is to help visitors find vehicles, answer questions, and capture leads when appropriate.
Always maintain a professional, helpful, and polite tone.

## Personality
{$personality}

## Sales Approach
{$aggressiveness}

## Rules
- Be helpful, accurate, and concise.
- If unsure, say so honestly and suggest speaking to a real person.
- When a customer asks about vehicles, availability, pricing, or specific models, ALWAYS use the search_inventory tool. Never fabricate vehicle information.
- When showing vehicles from search results, include key details: year, make, model, price, mileage.
- Vehicle cards with images and action buttons will be displayed automatically beside your message when you search inventory. Keep your text brief (1-2 sentences) since the cards provide all the details.
- If the inventory search returns no results, inform the customer politely and suggest they speak with the team.
- When a customer provides their name, email, or phone, use capture_lead_info immediately. Do NOT ask for more details first.
- Keep responses under 200 words unless the customer asks for detailed information.
- Auto-detect the customer's language and respond in the same language.

## Image Understanding
- If the user sends an image, analyze it carefully.
- Identify the vehicle make, model, body type, color, and any other visible details.
- Use the search_inventory tool with those details to find matching vehicles.
PROMPT;

        // Add known visitor Info
        $visitorInfo = [];
        if ($conversation->visitor_name) $visitorInfo[] = "- Name: " . $conversation->visitor_name;
        if ($conversation->visitor_email) $visitorInfo[] = "- Email: " . $conversation->visitor_email;
        if ($conversation->visitor_phone) $visitorInfo[] = "- Phone: " . $conversation->visitor_phone;

        if (!empty($visitorInfo)) {
            $prompt .= "\n\n## Known Visitor Information\n" . implode("\n", $visitorInfo);
            $prompt .= "\nIf you already have the required contact info, DO NOT ask the customer for it again.";
        }

        // Add test drive scheduling context
        $testDriveConfig = TestDriveConfig::where('tenant_id', $config->tenant_id)->first();
        if ($testDriveConfig && $testDriveConfig->is_active) {
            $days = $testDriveConfig->available_days ?? [1,2,3,4,5];
            $dayNames = array_map(fn($d) => ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'][$d] ?? '', $days);
            $prompt .= "\n\n## Test Drive Scheduling";
            $prompt .= "\nTest drives are ENABLED for this dealership.";
            $prompt .= "\n- Available days: " . implode(', ', $dayNames);
            $prompt .= "\n- Hours: {$testDriveConfig->start_time} to {$testDriveConfig->end_time}";
            $prompt .= "\n- Duration: {$testDriveConfig->duration_minutes} minutes each";
            
            $prompt .= "\n\n### SCHEDULING RULES (STRICTLY FOLLOW THESE):";
            $prompt .= "\n1. When a customer wants a test drive, use get_available_test_drive_slots to check availability.";
            $prompt .= "\n2. If the customer does NOT provide a date, DO NOT say no slots are available. Use the tool to check the next 7 days and suggest the closest available slots.";
            $prompt .= "\n3. If they provide a date that is full or unavailable, use the tool to find and suggest alternative nearby slots.";
            $prompt .= "\n4. BEFORE booking, you MUST explicitly confirm the chosen date and time with the customer.";
            $prompt .= "\n5. Once confirmed, use book_test_drive. You MUST give the customer their 6-character booking code and tell them to save it.";
            $prompt .= "\n6. If they want to look up, reschedule, or cancel, ask for their booking code and use manage_test_drive.";
        } else {
            $prompt .= "\n\n## Test Drive Scheduling";
            $prompt .= "\nYou have tools available to schedule test drives. When a customer asks to book, schedule, or arrange a test drive, use the get_available_test_drive_slots tool to check availability.";
            $prompt .= "\nIf the tool returns no slots or fails, politely let the customer know and offer to connect them with the team.";
        }

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

        return $prompt;
    }

    // ─── Tool Definitions ───────────────────────────────────────────

    /**
     * Get all tool definitions available to the AI.
     */
    protected function getToolDefinitions(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_inventory',
                    'description' => 'Search the dealership vehicle inventory. Use this whenever the customer asks about available cars, specific makes/models, pricing, features, or wants to see what\'s in stock. Always use this instead of guessing.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => [
                                'type' => 'string',
                                'description' => 'Natural language search query describing what vehicles to look for (e.g., "BMW X5", "SUV under 50000", "family car with AWD").',
                            ],
                            'max_results' => [
                                'type' => 'integer',
                                'description' => 'Maximum number of results to return. Default 5.',
                            ],
                        ],
                        'required' => ['query'],
                    ],
                ],
            ],
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
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_available_test_drive_slots',
                    'description' => 'Get available test drive time slots. Call this when the user wants to book or schedule a test drive.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'from_date' => [
                                'type' => 'string',
                                'description' => 'Start date in YYYY-MM-DD format. Defaults to today.',
                            ],
                            'days' => [
                                'type' => 'integer',
                                'description' => 'Number of days to show availability for. Default 7.',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'book_test_drive',
                    'description' => 'Book a test drive after the user selects a date and time. Requires at minimum a date and time.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'date' => ['type' => 'string', 'description' => 'Date in YYYY-MM-DD format.'],
                            'time' => ['type' => 'string', 'description' => 'Time in HH:MM (24h) format.'],
                            'name' => ['type' => 'string', 'description' => 'Customer name.'],
                            'email' => ['type' => 'string', 'description' => 'Customer email.'],
                            'phone' => ['type' => 'string', 'description' => 'Customer phone.'],
                        ],
                        'required' => ['date', 'time'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'manage_test_drive',
                    'description' => 'Look up, reschedule, or cancel a test drive by its 6-character booking code.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'action' => [
                                'type' => 'string',
                                'enum' => ['lookup', 'cancel', 'reschedule'],
                                'description' => 'What action to take.',
                            ],
                            'booking_code' => [
                                'type' => 'string',
                                'description' => 'The 6-character booking code.',
                            ],
                            'new_date' => ['type' => 'string', 'description' => 'New date for rescheduling (YYYY-MM-DD).'],
                            'new_time' => ['type' => 'string', 'description' => 'New time for rescheduling (HH:MM).'],
                            'reason' => ['type' => 'string', 'description' => 'Reason for cancellation.'],
                        ],
                        'required' => ['action', 'booking_code'],
                    ],
                ],
            ],
        ];
    }

    // ─── Tool Execution ─────────────────────────────────────────────

    /**
     * Execute a single tool call and return structured result data.
     * Results are serialized to JSON and sent back to the AI.
     */
    protected function executeTool(ChatConversation $conversation, array $toolCall): array
    {
        $name = $toolCall['function']['name'] ?? '';
        $args = json_decode($toolCall['function']['arguments'] ?? '{}', true) ?? [];

        \Illuminate\Support\Facades\Log::info("Executing tool: {$name}", ['args' => $args]);

        $result = match ($name) {
            'search_inventory' => $this->handleSearchInventory($conversation, $args),
            'capture_lead_info' => $this->handleCaptureLead($conversation, $args),
            'get_available_test_drive_slots' => $this->handleGetTestDriveSlots($conversation, $args),
            'book_test_drive' => $this->handleBookTestDrive($conversation, $args),
            'manage_test_drive' => $this->handleManageTestDrive($conversation, $args),
            default => ['error' => "Unknown tool: {$name}"],
        };

        \Illuminate\Support\Facades\Log::info("Tool result: {$name}", ['result' => $result]);

        return $result;
    }

    protected function handleSearchInventory(ChatConversation $conversation, array $args): array
    {
        $query = $args['query'] ?? '';
        $limit = $args['max_results'] ?? 5;

        try {
            $results = $this->inventorySearch->searchFromMessage(
                $query,
                $conversation->tenant_id,
                $limit
            );

            if (empty($results)) {
                return [
                    'vehicles' => [],
                    'total_found' => 0,
                    'query' => $query,
                    'message' => 'No matching vehicles found in current inventory.',
                ];
            }

            return [
                'vehicles' => $results,
                'total_found' => count($results),
                'query' => $query,
            ];
        } catch (\Exception $e) {
            return ['error' => 'Inventory search failed: ' . $e->getMessage()];
        }
    }

    protected function handleCaptureLead(ChatConversation $conversation, array $args): array
    {
        $this->leadService->captureLead($conversation, $args);
        if (!empty($args['name']) && empty($conversation->visitor_name)) {
            $conversation->update(['visitor_name' => $args['name']]);
        }
        return ['saved' => true, 'name' => $args['name'] ?? null];
    }

    protected function handleGetTestDriveSlots(ChatConversation $conversation, array $args): array
    {
        try {
            $slots = $this->testDriveService->getAvailableSlots(
                $conversation->tenant_id,
                $args['from_date'] ?? null,
                $args['days'] ?? 7
            );

            if (empty($slots)) {
                return ['available' => false, 'slots' => [], 'message' => 'No test drive slots available right now.'];
            }

            return ['available' => true, 'slots' => $slots];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    protected function handleBookTestDrive(ChatConversation $conversation, array $args): array
    {
        try {
            $args['conversation_id'] = $conversation->id;

            // Pull visitor info from conversation if not provided
            if (empty($args['name']) && $conversation->visitor_name) {
                $args['name'] = $conversation->visitor_name;
            }
            if (empty($args['email']) && $conversation->visitor_email) {
                $args['email'] = $conversation->visitor_email;
            }
            if (empty($args['phone']) && $conversation->visitor_phone) {
                $args['phone'] = $conversation->visitor_phone;
            }

            $testDrive = $this->testDriveService->bookTestDrive($conversation->tenant_id, $args);
            event(new NewTestDriveBooked($testDrive));

            return [
                'success' => true,
                'booking_code' => $testDrive->booking_code,
                'date' => $testDrive->scheduled_date->format('l, F jS'),
                'start_time' => $testDrive->scheduled_time,
                'end_time' => $testDrive->end_time,
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function handleManageTestDrive(ChatConversation $conversation, array $args): array
    {
        $action = $args['action'] ?? 'lookup';
        $code = $args['booking_code'] ?? '';

        try {
            switch ($action) {
                case 'lookup':
                    $td = $this->testDriveService->lookupTestDrive($code);
                    if (!$td) {
                        return ['found' => false, 'booking_code' => $code];
                    }
                    return [
                        'found' => true,
                        'booking_code' => $td->booking_code,
                        'date' => $td->scheduled_date->format('l, F jS'),
                        'start_time' => $td->scheduled_time,
                        'end_time' => $td->end_time,
                        'status' => $td->status,
                        'vehicle' => $td->vehicle
                            ? "{$td->vehicle->year} {$td->vehicle->make} {$td->vehicle->model}"
                            : null,
                    ];

                case 'cancel':
                    $td = $this->testDriveService->cancelTestDrive($code, $args['reason'] ?? null);
                    return ['cancelled' => true, 'booking_code' => $td->booking_code];

                case 'reschedule':
                    if (empty($args['new_date']) || empty($args['new_time'])) {
                        return ['error' => 'Need new_date and new_time to reschedule.'];
                    }
                    $td = $this->testDriveService->rescheduleTestDrive($code, $args['new_date'], $args['new_time']);
                    return [
                        'rescheduled' => true,
                        'booking_code' => $td->booking_code,
                        'new_date' => $td->scheduled_date->format('l, F jS'),
                        'new_start_time' => $td->scheduled_time,
                        'new_end_time' => $td->end_time,
                    ];

                default:
                    return ['error' => "Unknown action: {$action}. Use lookup, cancel, or reschedule."];
            }
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // ─── Vision & Audio ─────────────────────────────────────────────

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
     * Transcribe audio using Groq Whisper.
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

    // ─── Bot Configuration Helpers ──────────────────────────────────

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

    // ─── Intent Detection ───────────────────────────────────────────

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
            'connect me',
            'connect with',
            'transfer me',
            'transfer to',
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
     * Detect lead capture opportunities.
     */
    protected function detectLeadIntent(string $visitorMessage, string $aiResponse): ?string
    {
        $lower = strtolower($visitorMessage);

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
}
