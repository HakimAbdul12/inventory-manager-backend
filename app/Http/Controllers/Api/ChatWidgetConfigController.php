<?php

namespace App\Http\Controllers\Api;

use App\Models\WorkspaceChatConfig;
use App\Services\Chat\ExternalInventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ChatWidgetConfigController extends Controller
{
    /**
     * Get the chat widget configuration for the current workspace.
     */
    public function show(Request $request): JsonResponse
    {
        $tenant = app('current_tenant');
        $config = WorkspaceChatConfig::withoutGlobalScope('tenant')
            ->firstOrCreate(
                ['tenant_id' => $tenant->id],
                array_merge(
                    ['widget_settings' => WorkspaceChatConfig::defaultWidgetSettings()],
                )
            );

        return response()->json([
            'config' => $config,
            'embed_code' => $this->generateEmbedCode($config),
        ]);
    }

    /**
     * Update the chat widget configuration.
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'bot_name' => 'sometimes|string|max:100',
            'bot_personality' => 'sometimes|in:' . implode(',', WorkspaceChatConfig::PERSONALITIES),
            'greeting_message' => 'sometimes|string|max:500',
            'widget_settings' => 'sometimes|array',
            'widget_settings.primary_color' => 'sometimes|string|max:20',
            'widget_settings.accent_color' => 'sometimes|string|max:20',
            'widget_settings.position' => 'sometimes|in:left,right',
            'widget_settings.auto_open_delay' => 'sometimes|integer|min:0|max:60',
            'widget_settings.dark_mode' => 'sometimes|boolean',
            'widget_settings.vdp_url_template' => 'sometimes|nullable|string|max:1000',
            'business_hours' => 'sometimes|array',
            'auto_human_handoff' => 'sometimes|boolean',
            'fallback_timeout_minutes' => 'sometimes|integer|min:1|max:60',
            'ai_aggressiveness' => 'sometimes|in:' . implode(',', WorkspaceChatConfig::AGGRESSIVENESS_LEVELS),
            'is_active' => 'sometimes|boolean',
            'allowed_domains' => 'sometimes|array',
            'allowed_domains.*' => 'string|max:255',
            'external_api_config' => 'sometimes|nullable|array',
            'external_api_config.enabled' => 'sometimes|boolean',
            'external_api_config.base_url' => 'sometimes|nullable|string|max:1000',
            'external_api_config.http_method' => 'sometimes|in:GET,POST',
            'external_api_config.auth' => 'sometimes|array',
            'external_api_config.auth.type' => 'sometimes|in:none,api_key,bearer,basic',
            'external_api_config.request' => 'sometimes|array',
            'external_api_config.response' => 'sometimes|array',
        ]);

        $tenant = app('current_tenant');
        $config = WorkspaceChatConfig::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        $config->update($request->only([
            'bot_name',
            'bot_personality',
            'greeting_message',
            'widget_settings',
            'business_hours',
            'auto_human_handoff',
            'fallback_timeout_minutes',
            'ai_aggressiveness',
            'is_active',
            'allowed_domains',
            'external_api_config',
        ]));

        return response()->json([
            'config' => $config->fresh(),
            'embed_code' => $this->generateEmbedCode($config),
        ]);
    }

    /**
     * Regenerate the widget API key.
     */
    public function regenerateKey(Request $request): JsonResponse
    {
        $tenant = app('current_tenant');
        $config = WorkspaceChatConfig::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        $config->regenerateApiKey();

        return response()->json([
            'config' => $config->fresh(),
            'embed_code' => $this->generateEmbedCode($config),
        ]);
    }

    /**
     * Get the embed code snippet.
     */
    public function embedCode(Request $request): JsonResponse
    {
        $tenant = app('current_tenant');
        $config = WorkspaceChatConfig::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        return response()->json([
            'embed_code' => $this->generateEmbedCode($config),
        ]);
    }

    /**
     * Test an external API configuration without saving it.
     */
    public function testExternalApi(Request $request, ExternalInventoryService $externalService): JsonResponse
    {
        $request->validate([
            'config' => 'required|array',
            'config.base_url' => 'required|string|max:1000',
            'config.http_method' => 'required|in:GET,POST',
            'config.auth' => 'sometimes|array',
            'config.request' => 'sometimes|array',
            'config.response' => 'sometimes|array',
        ]);

        $tenant = app('current_tenant');
        $chatConfig = WorkspaceChatConfig::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->first();

        $vdpTemplate = $chatConfig?->widget_settings['vdp_url_template'] ?? null;
        $result = $externalService->testConnection($request->input('config'), $vdpTemplate);

        return response()->json($result);
    }

    /**
     * Generate the embed code HTML snippet.
     */
    protected function generateEmbedCode(WorkspaceChatConfig $config): string
    {
        $baseUrl = rtrim(config('app.url'), '/');
        $key = $config->widget_api_key;

        return <<<HTML
<script src="{$baseUrl}/widget/widget.js" data-workspace-key="{$key}" async defer></script>
HTML;
    }
}
