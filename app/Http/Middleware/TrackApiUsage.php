<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackApiUsage
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if IP is blocked
        if (\App\Models\BlockedIp::where('ip_address', $request->ip())->exists()) {
            return response()->json(['message' => 'Your IP address has been blocked.'], 403);
        }

        $startTime = microtime(true);

        $response = $next($request);

        $endTime = microtime(true);

        try {
            \App\Models\ApiUsageLog::create([
                'user_id' => $request->user()?->id,
                'method' => $request->method(),
                'path' => $request->path(),
                'status_code' => $response->getStatusCode(),
                'duration_ms' => ($endTime - $startTime) * 1000,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (\Throwable $e) {
            // Fail silently to avoid interrupting the API response
            // Log::error('Failed to log API usage: ' . $e->getMessage());
        }

        return $response;
    }
}
