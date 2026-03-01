<?php

namespace App\Services\Chat;

use App\Models\ChatConversation;
use App\Models\ChatWidgetMessage;
use App\Models\TelegramConnection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramBotService
{
    protected ?string $botToken;
    protected string $apiBase;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token') ?? '';
        $this->apiBase = "https://api.telegram.org/bot{$this->botToken}";
    }

    /**
     * Send a message to a Telegram chat.
     */
    public function sendMessage(string $chatId, string $text, array $options = []): ?array
    {
        try {
            $payload = [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => $options['parse_mode'] ?? 'HTML',
            ];

            if (!empty($options['reply_markup'])) {
                $payload['reply_markup'] = json_encode($options['reply_markup']);
            }

            $response = Http::post("{$this->apiBase}/sendMessage", $payload);

            if (!$response->successful()) {
                Log::error('Telegram sendMessage failed', [
                    'chat_id' => $chatId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            return $response->json('result');
        } catch (\Exception $e) {
            Log::error('Telegram sendMessage exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Notify available agents of a human handoff request.
     * $agents is a collection of App\Models\TelegramAgent
     */
    public function notifyHandoff(\Illuminate\Support\Collection $agents, ChatConversation $conversation): bool
    {
        if ($agents->isEmpty()) {
            return false;
        }

        $visitorName = $conversation->visitor_name ?? 'Unknown Visitor';
        $lastMessages = $conversation->messages()
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->sortBy('created_at');

        $contextText = $lastMessages->map(function (ChatWidgetMessage $msg) {
            $sender = match ($msg->sender_type) {
                'visitor' => '👤 <b>Customer</b>',
                'ai' => '🤖 <b>AI</b>',
                default => '🔧 <b>System</b>',
            };
            return "{$sender}:\n{$msg->content}\n";
        })->implode("\n");

        $message = <<<MSG
🔔 <b>Human Handoff Requested</b>

<b>Customer:</b> {$visitorName}
<b>Conversation ID:</b> <code>{$conversation->id}</code>

<b>Recent Messages:</b>
{$contextText}

Reply to this message to respond to the customer.
Use /close to end the conversation.
MSG;

        $notifiedAgents = [];

        foreach ($agents as $agent) {
            $result = $this->sendMessage($agent->telegram_chat_id, $message, [
                'reply_markup' => [
                    'inline_keyboard' => [
                        [
                            ['text' => '✅ Accept', 'callback_data' => "accept:{$conversation->id}"],
                        ],
                    ],
                ],
            ]);

            if ($result && isset($result['message_id'])) {
                $notifiedAgents[] = [
                    'chat_id' => $agent->telegram_chat_id,
                    'message_id' => $result['message_id'],
                ];
            }
        }

        if (count($notifiedAgents) > 0) {
            $conversation->update(['telegram_notified_agents' => $notifiedAgents]);
            return true;
        }

        return false;
    }

    /**
     * Forward a visitor message to the active agent via Telegram.
     */
    public function forwardToDealer(ChatConversation $conversation, string $message): bool
    {
        if (empty($conversation->agent_telegram_chat_id)) {
            return false;
        }

        $visitorName = $conversation->visitor_name ?? 'Customer';
        $text = "👤 <b>{$visitorName}:</b>\n{$message}";

        $result = $this->sendMessage($conversation->agent_telegram_chat_id, $text);

        return $result !== null;
    }

    /**
     * Process an incoming Telegram webhook update.
     * Routes the dealer's response back to the correct conversation.
     */
    public function handleWebhookUpdate(array $update): ?array
    {
        // Handle callback queries (button presses)
        if (isset($update['callback_query'])) {
            return $this->handleCallbackQuery($update['callback_query']);
        }

        // Handle text messages (dealer responses)
        if (isset($update['message']['text'])) {
            return $this->handleTextMessage($update['message']);
        }

        return null;
    }

    /**
     * Handle callback query from inline buttons.
     */
    protected function handleCallbackQuery(array $query): ?array
    {
        $data = $query['data'] ?? '';
        $chatId = $query['message']['chat']['id'] ?? null;
        $fromFirstName = $query['from']['first_name'] ?? 'An Agent';
        $fromUsername = $query['from']['username'] ?? null;

        if (!$chatId) return null;

        /**
         * We need to "answer" the callback query to remove the loading state on the button tap
         */
        $answerPayload = ['callback_query_id' => $query['id']];

        [$action, $conversationId] = explode(':', $data, 2) + [null, null];

        if (!$conversationId) {
            Http::post("{$this->apiBase}/answerCallbackQuery", $answerPayload);
            return null;
        }

        $conversation = ChatConversation::withoutGlobalScope('tenant')
            ->find($conversationId);

        if (!$conversation) {
            $answerPayload['text'] = '❌ Conversation not found.';
            $answerPayload['show_alert'] = true;
            Http::post("{$this->apiBase}/answerCallbackQuery", $answerPayload);
            return null;
        }

        // Try to handle "accept" logic with Racing Conditions
        if ($action === 'accept') {

            // Check if it's already accepted by someone else
            if ($conversation->state === ChatConversation::STATE_HUMAN && $conversation->agent_telegram_chat_id) {
                if ($conversation->agent_telegram_chat_id == $chatId) {
                    $answerPayload['text'] = 'You have already accepted this chat.';
                } else {
                    $answerPayload['text'] = 'This chat was already claimed by another agent.';
                    $answerPayload['show_alert'] = true;
                }
                Http::post("{$this->apiBase}/answerCallbackQuery", $answerPayload);
                return null;
            }

            $previousState = $conversation->state;

            // Mark this agent as the owner of the chat
            $conversation->update([
                'state' => ChatConversation::STATE_HUMAN,
                'agent_telegram_chat_id' => $chatId,
                'last_activity_at' => now(),
            ]);

            // Notify winner
            $this->sendMessage($chatId, "✅ You're now connected. Simply type your messages to reply. Send /close to end the chat.");

            // Answer callback quietly
            Http::post("{$this->apiBase}/answerCallbackQuery", $answerPayload);

            // Button Cleanup: Edit the message for other notified agents to remove the button and declare winner
            if (!empty($conversation->telegram_notified_agents)) {
                $winnerName = $fromUsername ? "@{$fromUsername}" : $fromFirstName;
                foreach ($conversation->telegram_notified_agents as $notified) {
                    if ($notified['chat_id'] != $chatId) {
                        try {
                            Http::post("{$this->apiBase}/editMessageText", [
                                'chat_id' => $notified['chat_id'],
                                'message_id' => $notified['message_id'],
                                'text' => "<i>(Chat was accepted by {$winnerName})</i>\n\n<b>Conversation ID:</b> <code>{$conversation->id}</code>",
                                'parse_mode' => 'HTML',
                            ]);
                        } catch (\Exception $e) {
                            // ignore cleanup errors
                        }
                    } else {
                        // For the winner, remove the inline keyboard so they can't click it again.
                        try {
                            Http::post("{$this->apiBase}/editMessageReplyMarkup", [
                                'chat_id' => $chatId,
                                'message_id' => $notified['message_id'],
                                'reply_markup' => json_encode(['inline_keyboard' => []]),
                            ]);
                        } catch (\Exception $e) {
                            // ignore
                        }
                    }
                }
            }

            // Store the winning agent's name (prioritize our custom name if set)
            $agent = \App\Models\TelegramAgent::where('tenant_id', $conversation->tenant_id)
                ->where('telegram_chat_id', $chatId)
                ->first();
            $displayName = $agent?->custom_name ?: $fromFirstName;

            return [
                'action' => 'accepted',
                'conversation_id' => $conversationId,
                'previous_state' => $previousState,
                'tenant_id' => $conversation->tenant_id,
                'agent_name' => $displayName,
            ];
        }

        Http::post("{$this->apiBase}/answerCallbackQuery", $answerPayload);
        return null;
    }

    /**
     * Handle a text message from the dealer — relay to the widget conversation.
     */
    protected function handleTextMessage(array $message): ?array
    {
        $chatId = (string) ($message['chat']['id'] ?? '');
        $text = $message['text'] ?? '';

        if (empty($chatId) || empty($text)) return null;

        // Handle commands
        if (str_starts_with($text, '/close')) {
            return $this->handleCloseCommand($chatId, $text);
        }

        // Check if message is a connection code (e.g. 6 chars, uppercase)
        if (strlen($text) === 6 && strtoupper($text) === $text && ctype_alnum($text)) {
            $connection = TelegramConnection::withoutGlobalScope('tenant')
                ->where('connection_code', $text)
                ->first();

            if ($connection && $connection->hasValidCode()) {
                // Ensure this chat ID isn't already connected to this tenant
                $existingAgent = \App\Models\TelegramAgent::where('tenant_id', $connection->tenant_id)
                    ->where('telegram_chat_id', $chatId)
                    ->first();

                if (!$existingAgent) {
                    \App\Models\TelegramAgent::create([
                        'tenant_id' => $connection->tenant_id,
                        'telegram_chat_id' => $chatId,
                        'first_name' => $message['from']['first_name'] ?? null,
                        'username' => $message['from']['username'] ?? null,
                        'is_active' => true,
                    ]);

                    // Verify connection record is active
                    $connection->update([
                        'connection_code' => null, // One-time use
                        'verified_at' => now(),
                        'is_active' => true,
                    ]);

                    $this->sendMessage($chatId, "✅ <b>Connection Successful!</b>\n\nYour Telegram account is now linked to the workspace. You will receive notifications here when a customer requests a human agent.");
                } else {
                    $this->sendMessage($chatId, "⚠️ You are already connected to this workspace!");
                }

                return ['action' => 'connection_linked'];
            }
        }

        // Find active conversation claimed by THIS specific Telegram agent
        $conversation = ChatConversation::withoutGlobalScope('tenant')
            ->where('agent_telegram_chat_id', $chatId)
            ->where('state', ChatConversation::STATE_HUMAN)
            ->latest('last_activity_at')
            ->first();

        if (!$conversation) {
            $this->sendMessage($chatId, '⚠️ No active conversation found. The customer may have disconnected.');
            return null;
        }

        // Get the agent's name for broadcasting
        $agent = \App\Models\TelegramAgent::where('tenant_id', $conversation->tenant_id)
            ->where('telegram_chat_id', $chatId)
            ->first();
        $agentName = $agent?->custom_name ?: ($agent?->first_name ?: 'Support Agent');

        // Store the dealer's message in the conversation
        $msg = $conversation->messages()->create([
            'sender_type' => ChatWidgetMessage::SENDER_HUMAN,
            'content' => $text,
            'message_type' => ChatWidgetMessage::TYPE_TEXT,
        ]);

        $conversation->touchActivity();

        return [
            'action' => 'message_relayed',
            'conversation_id' => $conversation->id,
            'agent_name' => $agentName,
            'message' => [
                'id' => $msg->id,
                'content' => $msg->content,
                'sender_type' => $msg->sender_type,
                'message_type' => $msg->message_type,
                'created_at' => $msg->created_at->toISOString(),
            ],
        ];
    }

    /**
     * Handle /close command.
     */
    protected function handleCloseCommand(string $chatId, string $text): ?array
    {
        $conversation = ChatConversation::withoutGlobalScope('tenant')
            ->where('agent_telegram_chat_id', $chatId)
            ->active()
            ->latest('last_activity_at')
            ->first();

        if ($conversation) {
            $previousState = $conversation->state;
            $conversation->resumeAI();
            $this->sendMessage($chatId, '🤖 <b>Conversation handed back to AI.</b>');

            return [
                'action' => 'handed_back',
                'conversation_id' => $conversation->id,
                'previous_state' => $previousState,
                'tenant_id' => $conversation->tenant_id,
            ];
        }

        $this->sendMessage($chatId, '⚠️ No active conversation to close.');
        return null;
    }

    /**
     * Set the webhook URL for the Telegram bot.
     */
    public function setWebhook(string $url, string $secretToken = ''): bool
    {
        $payload = ['url' => $url];
        if (!empty($secretToken)) {
            $payload['secret_token'] = $secretToken;
        }

        $response = Http::post("{$this->apiBase}/setWebhook", $payload);

        return $response->successful() && ($response->json('ok') === true);
    }

    /**
     * Send a test message to verify the connection.
     */
    public function sendTestMessage(string $chatId): bool
    {
        $result = $this->sendMessage($chatId, '✅ <b>Connection verified!</b> Your AI chat widget is now linked to this Telegram chat.');

        return $result !== null;
    }
}
