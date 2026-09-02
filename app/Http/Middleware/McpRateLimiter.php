<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * MCP-specific rate limiting middleware.
 *
 * Limits tool calls per user to prevent abuse from external AI clients.
 * Returns proper JSON-RPC error responses when rate limited.
 */
class McpRateLimiter
{
    /**
     * Maximum tool calls per minute per user.
     */
    protected const MAX_CALLS_PER_MINUTE = 60;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $key = 'mcp:rate:' . $user->id;
        $maxAttempts = self::MAX_CALLS_PER_MINUTE;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($key);

            return response()->json([
                'jsonrpc' => '2.0',
                'id' => $request->json('id'),
                'error' => [
                    'code' => -32000,
                    'message' => "Rate limit exceeded. Maximum {$maxAttempts} tool calls per minute. Retry after {$retryAfter} seconds.",
                    'data' => [
                        'retry_after_seconds' => $retryAfter,
                    ],
                ],
            ], 429);
        }

        RateLimiter::hit($key, 60);

        return $next($request);
    }
}
