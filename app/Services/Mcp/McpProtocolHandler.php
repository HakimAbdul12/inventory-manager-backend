<?php

namespace App\Services\Mcp;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Handles MCP JSON-RPC 2.0 protocol messages.
 *
 * Routes incoming requests to the appropriate handler methods,
 * formats responses per the MCP specification, and handles errors
 * with proper JSON-RPC error codes.
 */
class McpProtocolHandler
{
    protected McpSessionManager $sessions;
    protected McpToolRegistry $tools;

    // MCP protocol version
    protected const PROTOCOL_VERSION = '2025-03-26';

    // JSON-RPC error codes
    protected const PARSE_ERROR = -32700;
    protected const INVALID_REQUEST = -32600;
    protected const METHOD_NOT_FOUND = -32601;
    protected const INVALID_PARAMS = -32602;
    protected const INTERNAL_ERROR = -32603;

    public function __construct(McpSessionManager $sessions, McpToolRegistry $tools)
    {
        $this->sessions = $sessions;
        $this->tools = $tools;
    }

    /**
     * Handle an incoming JSON-RPC message (or batch of messages).
     *
     * @param  mixed       $payload    Decoded JSON payload
     * @param  User        $user       Authenticated user
     * @param  Tenant      $tenant     Active tenant
     * @param  string|null $sessionId  Current MCP session ID (from header)
     * @return array|null  Response(s) to send back, or null for notifications
     */
    public function handle(mixed $payload, User $user, Tenant $tenant, ?string $sessionId = null): array|null
    {
        // Batch request (array of JSON-RPC messages)
        if (is_array($payload) && isset($payload[0])) {
            $responses = [];
            foreach ($payload as $message) {
                $response = $this->handleSingleMessage($message, $user, $tenant, $sessionId);
                if ($response !== null) {
                    $responses[] = $response;
                }
            }
            return empty($responses) ? null : $responses;
        }

        // Single request
        return $this->handleSingleMessage($payload, $user, $tenant, $sessionId);
    }

    /**
     * Handle a single JSON-RPC message.
     */
    protected function handleSingleMessage(array $message, User $user, Tenant $tenant, ?string $sessionId): ?array
    {
        $jsonrpc = $message['jsonrpc'] ?? null;
        $method = $message['method'] ?? null;
        $params = $message['params'] ?? [];
        $id = $message['id'] ?? null;

        // Validate JSON-RPC version
        if ($jsonrpc !== '2.0') {
            return $this->errorResponse($id, self::INVALID_REQUEST, 'Invalid JSON-RPC version. Must be "2.0".');
        }

        // No method = invalid request
        if (!$method) {
            return $this->errorResponse($id, self::INVALID_REQUEST, 'Missing "method" field.');
        }

        // Notifications (no id) don't get responses
        $isNotification = ($id === null);

        try {
            $result = match ($method) {
                'initialize' => $this->handleInitialize($params, $user, $tenant),
                'notifications/initialized' => null, // Client acknowledgment, no response needed
                'ping' => $this->handlePing(),
                'tools/list' => $this->handleToolsList($user, $tenant, $sessionId),
                'tools/call' => $this->handleToolsCall($params, $user, $tenant, $sessionId),
                'resources/list' => $this->handleResourcesList($user, $tenant),
                'resources/read' => $this->handleResourcesRead($params, $user, $tenant),
                default => throw new \RuntimeException("Method not found: {$method}"),
            };

            if ($isNotification) {
                return null;
            }

            if ($result === null) {
                return null;
            }

            return $this->successResponse($id, $result);
        } catch (\RuntimeException $e) {
            if (str_contains($e->getMessage(), 'Method not found')) {
                return $this->errorResponse($id, self::METHOD_NOT_FOUND, $e->getMessage());
            }
            if (str_contains($e->getMessage(), 'Permission denied')) {
                return $this->errorResponse($id, self::INVALID_PARAMS, $e->getMessage());
            }
            return $this->errorResponse($id, self::INTERNAL_ERROR, $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('MCP protocol error', [
                'method' => $method,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->errorResponse($id, self::INTERNAL_ERROR, 'Internal server error.');
        }
    }

    /**
     * Handle the initialize handshake.
     * Creates a new MCP session and returns server capabilities.
     */
    protected function handleInitialize(array $params, User $user, Tenant $tenant): array
    {
        $clientInfo = $params['clientInfo'] ?? [];
        $protocolVersion = $params['protocolVersion'] ?? '';

        $sessionId = $this->sessions->createSession($user, $tenant, $clientInfo);

        return [
            'protocolVersion' => self::PROTOCOL_VERSION,
            'capabilities' => [
                'tools' => [
                    'listChanged' => false,
                ],
                'resources' => [
                    'subscribe' => false,
                    'listChanged' => false,
                ],
            ],
            'serverInfo' => [
                'name' => config('app.name', 'Inventory Manager') . ' MCP Server',
                'version' => '1.0.0',
            ],
            'instructions' => $this->buildInstructions($tenant),
            '_sessionId' => $sessionId,
        ];
    }

    /**
     * Handle ping request.
     */
    protected function handlePing(): array
    {
        return []; // Empty result per MCP spec
    }

    /**
     * Handle tools/list — return available tools filtered by user permissions.
     */
    protected function handleToolsList(User $user, Tenant $tenant, ?string $sessionId): array
    {
        if ($sessionId) {
            $this->sessions->validateSession($sessionId);
        }

        return [
            'tools' => $this->tools->getToolsForUser($user, $tenant),
        ];
    }

    /**
     * Handle tools/call — execute a tool with permission checks.
     */
    protected function handleToolsCall(array $params, User $user, Tenant $tenant, ?string $sessionId): array
    {
        $toolName = $params['name'] ?? '';
        $arguments = $params['arguments'] ?? [];

        if (empty($toolName)) {
            throw new \RuntimeException('Missing tool name.');
        }

        // Track the call in the session
        if ($sessionId) {
            $this->sessions->incrementToolCalls($sessionId);
        }

        try {
            $result = $this->tools->executeTool($toolName, $arguments, $user, $tenant);

            return [
                'content' => $result,
                'isError' => false,
            ];
        } catch (\RuntimeException $e) {
            return [
                'content' => [
                    ['type' => 'text', 'text' => $e->getMessage()],
                ],
                'isError' => true,
            ];
        }
    }

    /**
     * Handle resources/list — return available MCP resources.
     */
    protected function handleResourcesList(User $user, Tenant $tenant): array
    {
        return [
            'resources' => [
                [
                    'uri' => 'inventory://categories',
                    'name' => 'Product Categories',
                    'description' => 'All available product categories in the system.',
                    'mimeType' => 'application/json',
                ],
                [
                    'uri' => 'inventory://tenant/config',
                    'name' => 'Workspace Configuration',
                    'description' => 'Current workspace settings and configuration.',
                    'mimeType' => 'application/json',
                ],
                [
                    'uri' => 'inventory://permissions',
                    'name' => 'Your Permissions',
                    'description' => 'Your current role and permission set within this workspace.',
                    'mimeType' => 'application/json',
                ],
            ],
        ];
    }

    /**
     * Handle resources/read — return the content of a specific resource.
     */
    protected function handleResourcesRead(array $params, User $user, Tenant $tenant): array
    {
        $uri = $params['uri'] ?? '';

        return match ($uri) {
            'inventory://categories' => $this->readCategoriesResource(),
            'inventory://tenant/config' => $this->readTenantConfigResource($tenant),
            'inventory://permissions' => $this->readPermissionsResource($user, $tenant),
            default => throw new \RuntimeException("Unknown resource: {$uri}"),
        };
    }

    // ─── Resource Readers ───────────────────────────────────────────

    protected function readCategoriesResource(): array
    {
        $categories = \App\Models\Category::orderBy('name')
            ->get(['id', 'name', 'slug', 'parent_id'])
            ->toArray();

        return [
            'contents' => [
                [
                    'uri' => 'inventory://categories',
                    'mimeType' => 'application/json',
                    'text' => json_encode($categories, JSON_PRETTY_PRINT),
                ],
            ],
        ];
    }

    protected function readTenantConfigResource(Tenant $tenant): array
    {
        $config = [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'description' => $tenant->description,
            'settings' => $tenant->settings,
            'member_count' => $tenant->getMemberCount(),
        ];

        return [
            'contents' => [
                [
                    'uri' => 'inventory://tenant/config',
                    'mimeType' => 'application/json',
                    'text' => json_encode($config, JSON_PRETTY_PRINT),
                ],
            ],
        ];
    }

    protected function readPermissionsResource(User $user, Tenant $tenant): array
    {
        $permissionService = app(\App\Services\PermissionService::class);
        $permissions = $permissionService->getUserPermissions($user, $tenant);

        $data = [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'tenant_id' => $tenant->id,
            'tenant_role' => $tenant->getMemberRole($user),
            'permissions' => $permissions,
        ];

        return [
            'contents' => [
                [
                    'uri' => 'inventory://permissions',
                    'mimeType' => 'application/json',
                    'text' => json_encode($data, JSON_PRETTY_PRINT),
                ],
            ],
        ];
    }

    // ─── Instructions ───────────────────────────────────────────────

    /**
     * Build contextual instructions for the AI client about this server.
     */
    protected function buildInstructions(Tenant $tenant): string
    {
        return "You are connected to the '{$tenant->name}' workspace on the Inventory Manager platform. "
            . "This is an automotive dealership inventory and CRM system. "
            . "You can search inventory, manage leads, book test drives, and perform various dealership operations. "
            . "All actions are scoped to this workspace and subject to the authenticated user's permissions. "
            . "Use the tools/list endpoint to discover available tools based on your permissions.";
    }

    // ─── Response Helpers ───────────────────────────────────────────

    protected function successResponse(?int $id, mixed $result): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $result,
        ];
    }

    protected function errorResponse(?int $id, int $code, string $message, mixed $data = null): array
    {
        $error = [
            'code' => $code,
            'message' => $message,
        ];

        if ($data !== null) {
            $error['data'] = $data;
        }

        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => $error,
        ];
    }
}
