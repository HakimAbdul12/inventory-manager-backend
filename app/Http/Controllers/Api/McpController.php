<?php

namespace App\Http\Controllers\Api;

use App\Services\Mcp\McpProtocolHandler;
use App\Services\Mcp\McpSessionManager;
use App\Services\Mcp\McpToolRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

/**
 * MCP (Model Context Protocol) HTTP endpoint.
 *
 * Implements the Streamable HTTP transport for MCP, handling:
 *   POST /mcp — Main message endpoint (initialize, tools/list, tools/call)
 *   GET  /mcp — SSE stream for server-to-client notifications
 *   DELETE /mcp — Session termination
 *
 * External AI clients (Claude Desktop, Cursor, custom agents) connect here
 * using a Sanctum bearer token. All actions are tenant-scoped and permission-gated.
 */
class McpController extends Controller
{
    protected McpProtocolHandler $protocol;
    protected McpSessionManager $sessions;
    protected McpToolRegistry $tools;

    public function __construct(
        McpProtocolHandler $protocol,
        McpSessionManager $sessions,
        McpToolRegistry $tools
    ) {
        $this->protocol = $protocol;
        $this->sessions = $sessions;
        $this->tools = $tools;
    }

    /**
     * POST /mcp — Main MCP message handler.
     *
     * Accepts JSON-RPC 2.0 requests per the MCP specification.
     * The first request must be an "initialize" call which creates a session.
     * Subsequent requests include the Mcp-Session-Id header.
     */
    public function handle(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenant = app('current_tenant');

        if (!$user || !$tenant) {
            return response()->json([
                'jsonrpc' => '2.0',
                'id' => null,
                'error' => [
                    'code' => -32600,
                    'message' => 'Authentication required. Provide a valid Sanctum bearer token.',
                ],
            ], 401);
        }

        // Parse the incoming JSON-RPC payload
        $payload = $request->json()->all();

        if (empty($payload)) {
            return response()->json([
                'jsonrpc' => '2.0',
                'id' => null,
                'error' => [
                    'code' => -32700,
                    'message' => 'Parse error: empty or invalid JSON body.',
                ],
            ], 400);
        }

        // Get session ID from header (not required for initialize)
        $sessionId = $request->header('Mcp-Session-Id');

        // Validate session for non-initialize requests
        $method = $payload['method'] ?? ($payload[0]['method'] ?? null);
        if ($method !== 'initialize' && $sessionId) {
            $session = $this->sessions->validateSession($sessionId);
            if (!$session) {
                return response()->json([
                    'jsonrpc' => '2.0',
                    'id' => $payload['id'] ?? null,
                    'error' => [
                        'code' => -32600,
                        'message' => 'Invalid or expired MCP session. Please re-initialize.',
                    ],
                ], 400);
            }
        }

        // Route through the protocol handler
        $response = $this->protocol->handle($payload, $user, $tenant, $sessionId);

        if ($response === null) {
            // Notification — no response body
            return response()->json(null, 202);
        }

        // Check if the response contains a session ID from initialization
        $httpResponse = response()->json($response);

        // If this was an initialize response, set the session header
        if (isset($response['result']['_sessionId'])) {
            $newSessionId = $response['result']['_sessionId'];

            // Remove the internal session ID from the response payload
            unset($response['result']['_sessionId']);
            $httpResponse = response()->json($response);
            $httpResponse->header('Mcp-Session-Id', $newSessionId);
        }

        return $httpResponse;
    }

    /**
     * GET /mcp — SSE endpoint for server-to-client notifications.
     *
     * Currently returns 405 as we don't support server-initiated notifications yet.
     * Future: stream real-time events (inventory changes, new leads, etc.).
     */
    public function sse(Request $request)
    {
        $sessionId = $request->header('Mcp-Session-Id');

        if (!$sessionId) {
            return response()->json([
                'error' => 'Mcp-Session-Id header required for SSE.',
            ], 400);
        }

        $session = $this->sessions->validateSession($sessionId);
        if (!$session) {
            return response()->json([
                'error' => 'Invalid or expired session.',
            ], 400);
        }

        // Return 200 with content-type to acknowledge the SSE connection
        // Real-time notifications can be implemented here in the future
        return response('event: ping\ndata: {"status":"connected"}\n\n', 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
        ]);
    }

    /**
     * DELETE /mcp — Terminate an MCP session.
     */
    public function terminate(Request $request): JsonResponse
    {
        $sessionId = $request->header('Mcp-Session-Id');

        if ($sessionId) {
            $this->sessions->terminateSession($sessionId);
        }

        return response()->json(['status' => 'session_terminated']);
    }

    /**
     * GET /mcp/tools — Dashboard endpoint to list all tools with metadata.
     * Used by the frontend to show the MCP tools catalog.
     */
    public function listTools(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenant = app('current_tenant');

        if (!$user || !$tenant) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $tools = $this->tools->getToolsWithMetadata($user, $tenant);

        // Group by category
        $grouped = collect($tools)->groupBy('category')->toArray();

        return response()->json([
            'tools' => $tools,
            'grouped' => $grouped,
            'total_count' => count($tools),
        ]);
    }

    /**
     * GET /mcp/sessions — Dashboard endpoint to view active MCP sessions.
     */
    public function listSessions(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenant = app('current_tenant');

        if (!$user || !$tenant) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $sessions = $this->sessions->getTenantSessions($tenant->id);

        // Enrich sessions with user names
        $userIds = collect($sessions)->pluck('user_id')->unique();
        $users = \App\Models\User::whereIn('id', $userIds)->pluck('name', 'id');

        $enriched = collect($sessions)->map(function ($session) use ($users) {
            $session['user_name'] = $users[$session['user_id']] ?? 'Unknown';
            return $session;
        })->values()->toArray();

        return response()->json([
            'sessions' => $enriched,
            'total_count' => count($enriched),
        ]);
    }

    /**
     * DELETE /mcp/sessions/{sessionId} — Revoke a specific MCP session.
     */
    public function revokeSession(Request $request, string $sessionId): JsonResponse
    {
        $this->sessions->terminateSession($sessionId);

        return response()->json(['status' => 'revoked']);
    }

    /**
     * GET /mcp/connection-info — Get connection details for external AI clients.
     */
    public function connectionInfo(Request $request): JsonResponse
    {
        $user = $request->user();
        $baseUrl = rtrim(config('app.url'), '/');

        return response()->json([
            'endpoint' => "{$baseUrl}/mcp",
            'transport' => 'streamable-http',
            'authentication' => [
                'type' => 'bearer',
                'description' => 'Use a Sanctum API token as the bearer token.',
                'instructions' => 'Generate an API key from Dashboard → Settings → API Keys, then use it as: Authorization: Bearer <token>',
            ],
            'example_config' => [
                'mcpServers' => [
                    config('app.name', 'InventoryManager') => [
                        'url' => "{$baseUrl}/mcp",
                        'headers' => [
                            'Authorization' => 'Bearer <your-sanctum-token>',
                        ],
                    ],
                ],
            ],
        ]);
    }
}
