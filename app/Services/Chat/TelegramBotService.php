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
     * Notify dealer of a human handoff request.
     */
    public function notifyHandoff(TelegramConnection $connection, ChatConversation $conversation): bool
    {
        if (!$connection->isReady()) {
            Log::warning('Telegram connection not ready for handoff', [
                'tenant_id' => $connection->tenant_id,
            ]);
            return false;
        }

        $visitorName = $conversation->visitor_name ?? 'Unknown Visitor';
        $lastMessages = $conversation->messages()
            ->orderByDesc('created_at')
            ->take(5)
            ->get()
            ->reverse();

        $contextText = $lastMessages->map(function (ChatWidgetMessage $msg) {
            $sender = match ($msg->sender_type) {
                'visitor' => '👤 Customer',
                'ai' => '🤖 AI',
                default => '🔧 System',
            };
            return "{$sender}: {$msg->content}";
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

        $result = $this->sendMessage($connection->telegram_chat_id, $message, [
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        ['text' => '✅ Accept', 'callback_data' => "accept:{$conversation->id}"],
                        ['text' => '❌ Close', 'callback_data' => "close:{$conversation->id}"],
                    ],
                ],
            ],
        ]);

        return $result !== null;
    }

    /**
     * Forward a visitor message to the dealer via Telegram.
     */
    public function forwardToDealer(TelegramConnection $connection, ChatConversation $conversation, string $message): bool
    {
        if (!$connection->isReady()) {
            return false;
        }

        $visitorName = $conversation->visitor_name ?? 'Customer';
        $text = "👤 <b>{$visitorName}:</b>\n{$message}\n\n<code>#{$conversation->id}</code>";

        $result = $this->sendMessage($connection->telegram_chat_id, $text);

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

        if (!$chatId) return null;

        [$action, $conversationId] = explode(':', $data, 2) + [null, null];

        if (!$conversationId) return null;

        $conversation = ChatConversation::withoutGlobalScope('tenant')
            ->find($conversationId);

        if (!$conversation) {
            $this->sendMessage($chatId, '❌ Conversation not found.');
            return null;
        }

        if ($action === 'accept') {
            $conversation->switchToHuman($chatId);
            $this->sendMessage($chatId, "✅ You're now connected. Simply type your messages to reply.");
            return ['action' => 'accepted', 'conversation_id' => $conversationId];
        }

        if ($action === 'close') {
            $conversation->close();
            $this->sendMessage($chatId, '✅ Conversation closed.');
            return ['action' => 'closed', 'conversation_id' => $conversationId];
        }

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

        // Find active conversation linked to this Telegram chat
        $conversation = ChatConversation::withoutGlobalScope('tenant')
            ->where('telegram_chat_id', $chatId)
            ->where('state', ChatConversation::STATE_HUMAN)
            ->latest('last_activity_at')
            ->first();

        if (!$conversation) {
            $this->sendMessage($chatId, '⚠️ No active conversation found. The customer may have disconnected.');
            return null;
        }

        // Store the dealer's message in the conversation
        $conversation->messages()->create([
            'sender_type' => ChatWidgetMessage::SENDER_HUMAN,
            'content' => $text,
            'message_type' => ChatWidgetMessage::TYPE_TEXT,
        ]);

        $conversation->touchActivity();

        return [
            'action' => 'message_relayed',
            'conversation_id' => $conversation->id,
            'content' => $text,
        ];
    }

    /**
     * Handle /close command.
     */
    protected function handleCloseCommand(string $chatId, string $text): ?array
    {
        $conversation = ChatConversation::withoutGlobalScope('tenant')
            ->where('telegram_chat_id', $chatId)
            ->active()
            ->latest('last_activity_at')
            ->first();

        if ($conversation) {
            $conversation->close();
            $this->sendMessage($chatId, '✅ Conversation closed. The AI will resume for new messages.');

            return ['action' => 'closed', 'conversation_id' => $conversation->id];
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
