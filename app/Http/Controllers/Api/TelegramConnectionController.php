<?php

namespace App\Http\Controllers\Api;

use App\Models\TelegramConnection;
use App\Services\Chat\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class TelegramConnectionController extends Controller
{
    protected TelegramBotService $telegramService;

    public function __construct(TelegramBotService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * Get the Telegram connection status for the current workspace.
     */
    public function show(): JsonResponse
    {
        $tenant = app('current_tenant');

        $connection = TelegramConnection::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->first();

        return response()->json([
            'connection' => $connection,
            'is_connected' => $connection?->isReady() ?? false,
            'bot_username' => config('services.telegram.bot_username', null),
        ]);
    }

    /**
     * Connect Telegram by setting the chat ID.
     */
    public function connect(Request $request): JsonResponse
    {
        $request->validate([
            'telegram_chat_id' => 'required|string|max:100',
            'auto_away_message' => 'sometimes|string|max:500',
            'agent_sla_minutes' => 'sometimes|integer|min:1|max:60',
        ]);

        $tenant = app('current_tenant');

        $connection = TelegramConnection::withoutGlobalScope('tenant')->updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'telegram_chat_id' => $request->telegram_chat_id,
                'connected_by' => $request->user()->id,
                'auto_away_message' => $request->auto_away_message,
                'agent_sla_minutes' => $request->agent_sla_minutes ?? 5,
            ]
        );

        return response()->json([
            'connection' => $connection,
            'message' => 'Telegram chat ID saved. Send a test message to verify.',
        ]);
    }

    /**
     * Send a test message to verify the Telegram connection.
     */
    public function test(): JsonResponse
    {
        $tenant = app('current_tenant');
        $connection = TelegramConnection::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->first();

        if (!$connection || empty($connection->telegram_chat_id)) {
            return response()->json(['error' => 'No Telegram connection configured.'], 400);
        }

        $success = $this->telegramService->sendTestMessage($connection->telegram_chat_id);

        if ($success) {
            $connection->markVerified();

            return response()->json([
                'success' => true,
                'message' => 'Test message sent! Check your Telegram.',
                'connection' => $connection->fresh(),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to send test message. Please verify your chat ID.',
        ], 400);
    }

    /**
     * Disconnect Telegram.
     */
    public function disconnect(): JsonResponse
    {
        $tenant = app('current_tenant');

        TelegramConnection::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->delete();

        return response()->json(['message' => 'Telegram disconnected.']);
    }

    /**
     * Update Telegram settings.
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $request->validate([
            'auto_away_message' => 'sometimes|string|max:500',
            'agent_sla_minutes' => 'sometimes|integer|min:1|max:60',
            'is_active' => 'sometimes|boolean',
        ]);

        $tenant = app('current_tenant');
        $connection = TelegramConnection::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        $connection->update($request->only([
            'auto_away_message',
            'agent_sla_minutes',
            'is_active',
        ]));

        return response()->json([
            'connection' => $connection->fresh(),
        ]);
    }
}
