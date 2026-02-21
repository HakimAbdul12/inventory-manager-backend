<?php

namespace App\Http\Controllers\Api;

use App\Services\Chat\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    protected TelegramBotService $telegramService;

    public function __construct(TelegramBotService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * Handle incoming Telegram webhook updates.
     */
    public function handle(Request $request): JsonResponse
    {
        // Verify secret token
        $secretToken = config('services.telegram.webhook_secret', '');
        if (!empty($secretToken)) {
            $headerToken = $request->header('X-Telegram-Bot-Api-Secret-Token', '');
            if ($headerToken !== $secretToken) {
                Log::warning('Telegram webhook: invalid secret token');
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }

        $update = $request->all();

        Log::debug('Telegram webhook received', [
            'update_id' => $update['update_id'] ?? null,
        ]);

        try {
            $result = $this->telegramService->handleWebhookUpdate($update);

            // If a message was relayed back to a conversation, broadcast it
            if ($result && ($result['action'] ?? '') === 'message_relayed') {
                // Broadcast via Reverb WebSocket so widget gets the message
                event(new \App\Events\WidgetMessageReceived(
                    $result['conversation_id'],
                    $result['content'],
                    'human_agent'
                ));
            }

            if ($result && in_array($result['action'] ?? '', ['accepted', 'closed'])) {
                event(new \App\Events\ConversationStateChanged(
                    $result['conversation_id'],
                    $result['action'] === 'accepted' ? 'human' : 'closed'
                ));
            }
        } catch (\Exception $e) {
            Log::error('Telegram webhook processing error', [
                'error' => $e->getMessage(),
            ]);
        }

        // Always return 200 to Telegram
        return response()->json(['ok' => true]);
    }
}
