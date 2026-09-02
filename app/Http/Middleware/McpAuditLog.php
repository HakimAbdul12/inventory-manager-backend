<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * MCP audit logging middleware.
 *
 * Records every MCP tool call with user, tenant, tool name, arguments,
 * and result summary. Integrates with the existing ActivityLogger service.
 */
class McpAuditLog
{
    protected ActivityLogger $logger;

    public function __construct(ActivityLogger $logger)
    {
        $this->logger = $logger;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only log tool calls, not list/initialize requests
        $payload = $request->json()->all();
        $method = $payload['method'] ?? null;

        if ($method !== 'tools/call') {
            return $response;
        }

        $user = $request->user();
        $tenant = app('current_tenant');
        $toolName = $payload['params']['name'] ?? 'unknown';
        $arguments = $payload['params']['arguments'] ?? [];

        try {
            $this->logger
                ->causedBy($user)
                ->withDescription("MCP tool '{$toolName}' called via external AI client")
                ->withProperties([
                    'tool_name' => $toolName,
                    'arguments' => $this->sanitizeArgs($arguments),
                    'session_id' => $request->header('Mcp-Session-Id'),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'response_status' => $response->getStatusCode(),
                ])
                ->log('mcp.tool_call');
        } catch (\Throwable $e) {
            // Fail silently to avoid disrupting the MCP response
            \Illuminate\Support\Facades\Log::warning('MCP audit log failed', [
                'error' => $e->getMessage(),
            ]);
        }

        return $response;
    }

    /**
     * Sanitize arguments for logging — redact any sensitive fields.
     */
    protected function sanitizeArgs(array $args): array
    {
        $sensitive = ['password', 'token', 'secret', 'api_key', 'ssn', 'credit_card'];

        foreach ($args as $key => $value) {
            if (in_array(strtolower($key), $sensitive, true)) {
                $args[$key] = '***REDACTED***';
            }
        }

        return $args;
    }
}
