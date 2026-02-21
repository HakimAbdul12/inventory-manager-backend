<?php

namespace App\Http\Controllers\Api;

use App\Models\ChatAnalytic;
use App\Models\ChatConversation;
use App\Models\ChatWidgetMessage;
use App\Models\WorkspaceChatConfig;
use App\Services\Chat\ChatAIService;
use App\Services\Chat\LeadCaptureService;
use App\Services\Chat\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class WidgetConversationController extends Controller
{
    protected ChatAIService $aiService;
    protected TelegramBotService $telegramService;
    protected LeadCaptureService $leadService;

    public function __construct(
        ChatAIService $aiService,
        TelegramBotService $telegramService,
        LeadCaptureService $leadService
    ) {
        $this->aiService = $aiService;
        $this->telegramService = $telegramService;
        $this->leadService = $leadService;
    }

    /**
     * Get widget display configuration (public, no auth needed).
     */
    public function config(string $apiKey): JsonResponse
    {
        $config = WorkspaceChatConfig::withoutGlobalScope('tenant')
            ->where('widget_api_key', $apiKey)
            ->where('is_active', true)
            ->first();

        if (!$config) {
            return response()->json(['error' => 'Widget not found or inactive'], 404);
        }

        return response()->json([
            'bot_name' => $config->bot_name,
            'greeting_message' => $config->greeting_message,
            'widget_settings' => $config->widget_settings ?? WorkspaceChatConfig::defaultWidgetSettings(),
            'is_within_business_hours' => $config->isWithinBusinessHours(),
        ]);
    }

    /**
     * Get widget configuration by tenant ID.
     */
    public function configByTenant(string $tenantId): JsonResponse
    {
        $config = WorkspaceChatConfig::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->first();

        if (!$config) {
            return response()->json(['error' => 'Widget not found or inactive'], 404);
        }

        return response()->json([
            'api_key' => $config->widget_api_key,
            'bot_name' => $config->bot_name,
            'greeting_message' => $config->greeting_message,
            'widget_settings' => $config->widget_settings ?? WorkspaceChatConfig::defaultWidgetSettings(),
            'is_within_business_hours' => $config->isWithinBusinessHours(),
        ]);
    }

    /**
     * Start a new conversation.
     */
    public function start(Request $request, string $apiKey): JsonResponse
    {
        $config = $this->resolveConfig($apiKey);
        if (!$config) {
            return response()->json(['error' => 'Widget not found or inactive'], 404);
        }

        $conversation = ChatConversation::withoutGlobalScope('tenant')->create([
            'tenant_id' => $config->tenant_id,
            'metadata' => [
                'user_agent' => $request->userAgent(),
                'referrer' => $request->header('Referer'),
                'ip_hash' => hash('sha256', $request->ip() . config('app.key')),
                'language' => $request->header('Accept-Language'),
            ],
            'last_activity_at' => now(),
        ]);

        // Update analytics
        ChatAnalytic::forToday($config->tenant_id)->incrementStat('total_conversations');

        // Send greeting message
        $greetingMessage = $conversation->messages()->create([
            'sender_type' => ChatWidgetMessage::SENDER_AI,
            'content' => $config->greeting_message,
            'message_type' => ChatWidgetMessage::TYPE_TEXT,
        ]);

        return response()->json([
            'session_token' => $conversation->session_token,
            'conversation_id' => $conversation->id,
            'greeting' => $greetingMessage,
            'bot_name' => $config->bot_name,
        ]);
    }

    /**
     * Handle a visitor message and get AI response.
     */
    public function message(Request $request, string $apiKey): JsonResponse
    {
        $request->validate([
            'session_token' => 'required|string',
            'message' => 'required|string|max:2000',
        ]);

        $config = $this->resolveConfig($apiKey);
        if (!$config) {
            return response()->json(['error' => 'Widget not found or inactive'], 404);
        }

        $conversation = ChatConversation::withoutGlobalScope('tenant')
            ->where('session_token', $request->session_token)
            ->where('tenant_id', $config->tenant_id)
            ->first();

        if (!$conversation) {
            return response()->json(['error' => 'Session not found'], 404);
        }

        if ($conversation->state === ChatConversation::STATE_CLOSED) {
            return response()->json(['error' => 'Conversation has been closed'], 410);
        }

        // Update analytics
        ChatAnalytic::forToday($config->tenant_id)->incrementStat('total_messages');

        // If in human mode, forward to Telegram
        if ($conversation->isHumanMode()) {
            return $this->handleHumanModeMessage($conversation, $config, $request->message);
        }

        // Process with AI
        $result = $this->aiService->processMessage($conversation, $config, $request->message);

        // If AI wants to trigger human handoff
        if (!empty($result['request_human_handoff'])) {
            $this->initiateHumanHandoff($conversation, $config);
        }

        return response()->json($result);
    }

    /**
     * Explicitly request human handoff.
     */
    public function requestHuman(Request $request, string $apiKey): JsonResponse
    {
        $request->validate([
            'session_token' => 'required|string',
        ]);

        $config = $this->resolveConfig($apiKey);
        if (!$config) {
            return response()->json(['error' => 'Widget not found or inactive'], 404);
        }

        $conversation = ChatConversation::withoutGlobalScope('tenant')
            ->where('session_token', $request->session_token)
            ->where('tenant_id', $config->tenant_id)
            ->active()
            ->first();

        if (!$conversation) {
            return response()->json(['error' => 'Active session not found'], 404);
        }

        $result = $this->initiateHumanHandoff($conversation, $config);

        ChatAnalytic::forToday($config->tenant_id)->incrementStat('human_handoff_count');

        return response()->json($result);
    }

    /**
     * Submit lead information.
     */
    public function submitLead(Request $request, string $apiKey): JsonResponse
    {
        $request->validate([
            'session_token' => 'required|string',
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255',
            'phone' => 'sometimes|string|max:50',
            'intent' => 'sometimes|string',
            'vehicle_id' => 'sometimes|uuid',
        ]);

        $config = $this->resolveConfig($apiKey);
        if (!$config) {
            return response()->json(['error' => 'Widget not found or inactive'], 404);
        }

        $conversation = ChatConversation::withoutGlobalScope('tenant')
            ->where('session_token', $request->session_token)
            ->where('tenant_id', $config->tenant_id)
            ->first();

        if (!$conversation) {
            return response()->json(['error' => 'Session not found'], 404);
        }

        $lead = $this->leadService->captureLead($conversation, $request->only([
            'name',
            'email',
            'phone',
            'intent',
            'vehicle_id',
        ]));

        return response()->json([
            'lead' => $lead,
            'message' => 'Thank you! Our team will reach out to you soon.',
        ]);
    }

    // ─── Private Helpers ────────────────────────────────────

    protected function resolveConfig(string $apiKey): ?WorkspaceChatConfig
    {
        return WorkspaceChatConfig::withoutGlobalScope('tenant')
            ->where('widget_api_key', $apiKey)
            ->where('is_active', true)
            ->first();
    }

    protected function handleHumanModeMessage(
        ChatConversation $conversation,
        WorkspaceChatConfig $config,
        string $message
    ): JsonResponse {
        // Store visitor message
        $conversation->messages()->create([
            'sender_type' => ChatWidgetMessage::SENDER_VISITOR,
            'content' => $message,
            'message_type' => ChatWidgetMessage::TYPE_TEXT,
        ]);

        $conversation->touchActivity();

        // Forward to Telegram
        $telegramConnection = $config->tenant
            ? \App\Models\TelegramConnection::withoutGlobalScope('tenant')
            ->where('tenant_id', $config->tenant_id)
            ->first()
            : null;

        if ($telegramConnection) {
            $this->telegramService->forwardToDealer($telegramConnection, $conversation, $message);
        }

        return response()->json([
            'message' => [
                'content' => $message,
                'sender_type' => 'visitor',
                'message_type' => 'text',
            ],
            'state' => 'human',
            'info' => 'Message sent to agent.',
        ]);
    }

    protected function initiateHumanHandoff(
        ChatConversation $conversation,
        WorkspaceChatConfig $config
    ): array {
        $telegramConnection = \App\Models\TelegramConnection::withoutGlobalScope('tenant')
            ->where('tenant_id', $config->tenant_id)
            ->where('is_active', true)
            ->first();

        if (!$telegramConnection || !$telegramConnection->isReady()) {
            // No Telegram connection — AI informs customer
            $msg = $conversation->messages()->create([
                'sender_type' => ChatWidgetMessage::SENDER_AI,
                'content' => "I'm sorry, our team is currently unavailable. Could you please leave your contact details and we'll get back to you as soon as possible?",
                'message_type' => ChatWidgetMessage::TYPE_LEAD_FORM,
            ]);

            return [
                'message' => $msg,
                'state' => 'ai',
                'show_lead_form' => true,
            ];
        }

        // Send notification to Telegram
        $this->telegramService->notifyHandoff($telegramConnection, $conversation);

        // Transition state
        $conversation->transitionTo(ChatConversation::STATE_HUMAN);

        $msg = $conversation->messages()->create([
            'sender_type' => ChatWidgetMessage::SENDER_AI,
            'content' => "I've notified our team! A real person will be with you shortly. In the meantime, feel free to continue chatting — your messages will be forwarded to them. 🙋",
            'message_type' => ChatWidgetMessage::TYPE_SYSTEM,
        ]);

        return [
            'message' => $msg,
            'state' => 'human',
        ];
    }
}
