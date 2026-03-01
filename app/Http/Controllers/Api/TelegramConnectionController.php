<?php

namespace App\Http\Controllers\Api;

use App\Models\TelegramConnection;
use App\Models\TelegramAgent;
use App\Services\Chat\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

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
            'data' => $connection,
            'is_connected' => $connection?->isReady() ?? false,
            'bot_username' => config('services.telegram.bot_username', null),
        ]);
    }

    /**
     * Start the Telegram connection process by generating a connection code.
     * The user will send this code to the bot to link their chat.
     */
    public function connect(Request $request): JsonResponse
    {
        $tenant = app('current_tenant');

        // Generate a random 6-char code
        $code = strtoupper(Str::random(6));

        $connection = TelegramConnection::withoutGlobalScope('tenant')->updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'connection_code' => $code,
                'connection_code_expires_at' => now()->addHours(24),
                'connected_by' => $request->user()->id,
            ]
        );

        return response()->json([
            'data' => [
                'connection_code' => $code,
                'details' => $connection,
            ],
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

    /**
     * Get the list of connected Telegram agents.
     */
    public function agents(): JsonResponse
    {
        $tenant = app('current_tenant');

        $agents = TelegramAgent::where('tenant_id', $tenant->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $agents
        ]);
    }

    /**
     * Update a specific Telegram agent (e.g., set custom name).
     */
    public function updateAgent(Request $request, string $agentId): JsonResponse
    {
        $request->validate([
            'custom_name' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        $tenant = app('current_tenant');

        $agent = TelegramAgent::where('tenant_id', $tenant->id)
            ->where('id', $agentId)
            ->firstOrFail();

        $agent->update($request->only([
            'custom_name',
            'is_active',
        ]));

        return response()->json([
            'message' => 'Agent updated successfully.',
            'data' => $agent->fresh(),
        ]);
    }

    /**
     * Remove a specific Telegram agent.
     */
    public function removeAgent(string $agentId): JsonResponse
    {
        $tenant = app('current_tenant');

        $agent = TelegramAgent::where('tenant_id', $tenant->id)
            ->where('id', $agentId)
            ->firstOrFail();

        $agent->delete();

        return response()->json([
            'message' => 'Agent removed successfully.'
        ]);
    }

    /**
     * Get Telegram webhook info (for dashboard system status).
     */
    public function webhookInfo(): JsonResponse
    {
        $botToken = config('services.telegram.bot_token');
        if (empty($botToken)) {
            return response()->json(['data' => ['url' => null, 'pending_update_count' => 0]]);
        }

        try {
            $response = \Illuminate\Support\Facades\Http::get(
                "https://api.telegram.org/bot{$botToken}/getWebhookInfo"
            );

            if ($response->successful()) {
                $result = $response->json('result', []);
                return response()->json([
                    'data' => [
                        'url' => $result['url'] ?? null,
                        'pending_update_count' => $result['pending_update_count'] ?? 0,
                        'last_error_message' => $result['last_error_message'] ?? null,
                        'last_error_date' => $result['last_error_date'] ?? null,
                    ],
                ]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to get Telegram webhook info', [
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['data' => ['url' => null, 'pending_update_count' => 0]]);
    }
}
