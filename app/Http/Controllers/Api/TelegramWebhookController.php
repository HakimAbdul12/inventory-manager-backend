<?php

namespace App\Http\Controllers\Api;

use App\Events\WidgetMessageSent;
use App\Events\WidgetStateChanged;
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
                Log::warning('Telegram webhook: invalid secret token', [
                    'expected_length' => strlen($secretToken),
                    'received_length' => strlen($headerToken),
                    'received_start' => substr($headerToken, 0, 3) . '...',
                    'received_end' => '...' . substr($headerToken, -3),
                ]);
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }

        $update = $request->all();

        Log::debug('Telegram webhook received', [
            'update_id' => $update['update_id'] ?? null,
            'payload' => json_encode($update),
        ]);

        try {
            $result = $this->telegramService->handleWebhookUpdate($update);

            if (!$result) {
                return response()->json(['ok' => true]);
            }

            $action = $result['action'] ?? '';

            // Dealer replied via Telegram → broadcast to widget + dashboard
            if ($action === 'message_relayed' && !empty($result['message'])) {
                $msg = $result['message'];
                $msg['agent_name'] = $result['agent_name'] ?? 'Support Agent';

                broadcast(new WidgetMessageSent(
                    $result['conversation_id'],
                    $msg
                ));
            }

            // Dealer accepted via Telegram inline button
            if ($action === 'accepted') {
                broadcast(new WidgetStateChanged(
                    $result['conversation_id'],
                    $result['previous_state'] ?? 'open',
                    'human',
                    $result['agent_name'] ?? 'Support Agent'
                ));
            }

            // Dealer closed conversation or handed back via Telegram
            if (in_array($action, ['closed', 'handed_back'])) {
                $newState = ($action === 'handed_back') ? 'ai' : 'closed';
                broadcast(new WidgetStateChanged(
                    $result['conversation_id'],
                    $result['previous_state'] ?? 'human',
                    $newState
                ));
            }

            // Agent used @ai command → AI response needs to be broadcast to widget
            if ($action === 'ai_command_executed' && !empty($result['message'])) {
                $msg = $result['message'];
                $msg['vehicle_cards'] = $result['vehicle_cards'] ?? [];

                broadcast(new WidgetMessageSent(
                    $result['conversation_id'],
                    $msg
                ));
            }
        } catch (\Exception $e) {
            Log::error('Telegram webhook processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // Always return 200 to Telegram
        return response()->json(['ok' => true]);
    }
}
