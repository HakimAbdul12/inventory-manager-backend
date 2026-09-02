<?php

namespace App\Services\Mcp;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Manages MCP session lifecycle.
 *
 * Each session is tied to an authenticated user + tenant and persists
 * in the cache for the duration of the connection. Sessions enforce
 * the MCP initialize → initialized handshake before allowing tool calls.
 */
class McpSessionManager
{
    /**
     * Session TTL in seconds (2 hours).
     */
    protected const SESSION_TTL = 7200;

    /**
     * Maximum concurrent sessions per user.
     */
    protected const MAX_SESSIONS_PER_USER = 10;

    /**
     * Create a new MCP session after successful initialization.
     */
    public function createSession(User $user, Tenant $tenant, array $clientInfo = []): string
    {
        // Enforce session limit
        $this->enforceSessionLimit($user);

        $sessionId = Str::uuid()->toString();

        $sessionData = [
            'id' => $sessionId,
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'client_info' => $clientInfo,
            'initialized' => true,
            'created_at' => now()->toIso8601String(),
            'last_activity' => now()->toIso8601String(),
            'tool_calls_count' => 0,
        ];

        Cache::put($this->sessionKey($sessionId), $sessionData, self::SESSION_TTL);
        $this->trackUserSession($user, $sessionId);

        Log::info('MCP session created', [
            'session_id' => $sessionId,
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'client_info' => $clientInfo,
        ]);

        return $sessionId;
    }

    /**
     * Validate that a session exists and is active.
     */
    public function validateSession(string $sessionId): ?array
    {
        $session = Cache::get($this->sessionKey($sessionId));

        if (!$session) {
            return null;
        }

        // Refresh TTL on activity
        $session['last_activity'] = now()->toIso8601String();
        Cache::put($this->sessionKey($sessionId), $session, self::SESSION_TTL);

        return $session;
    }

    /**
     * Check if a session has completed the initialize handshake.
     */
    public function isInitialized(string $sessionId): bool
    {
        $session = $this->validateSession($sessionId);
        return $session && ($session['initialized'] ?? false);
    }

    /**
     * Increment the tool call counter for a session.
     */
    public function incrementToolCalls(string $sessionId): void
    {
        $session = Cache::get($this->sessionKey($sessionId));
        if ($session) {
            $session['tool_calls_count'] = ($session['tool_calls_count'] ?? 0) + 1;
            $session['last_activity'] = now()->toIso8601String();
            Cache::put($this->sessionKey($sessionId), $session, self::SESSION_TTL);
        }
    }

    /**
     * Terminate a session.
     */
    public function terminateSession(string $sessionId): void
    {
        $session = Cache::get($this->sessionKey($sessionId));

        if ($session) {
            $this->removeUserSession($session['user_id'], $sessionId);
            Cache::forget($this->sessionKey($sessionId));

            Log::info('MCP session terminated', [
                'session_id' => $sessionId,
                'user_id' => $session['user_id'],
                'tool_calls' => $session['tool_calls_count'] ?? 0,
            ]);
        }
    }

    /**
     * Get all active sessions for a user.
     */
    public function getUserSessions(string $userId): array
    {
        $sessionIds = Cache::get($this->userSessionsKey($userId), []);
        $sessions = [];

        foreach ($sessionIds as $sessionId) {
            $session = Cache::get($this->sessionKey($sessionId));
            if ($session) {
                $sessions[] = $session;
            }
        }

        return $sessions;
    }

    /**
     * Get all active sessions for a tenant.
     */
    public function getTenantSessions(string $tenantId): array
    {
        // Scan through all tracked sessions for this tenant
        // In production with many users, this could be optimized with a tenant-level index
        $tenantSessionIds = Cache::get("mcp:tenant:{$tenantId}:sessions", []);
        $sessions = [];

        foreach ($tenantSessionIds as $sessionId) {
            $session = Cache::get($this->sessionKey($sessionId));
            if ($session) {
                $sessions[] = $session;
            }
        }

        return $sessions;
    }

    /**
     * Enforce maximum sessions per user.
     */
    protected function enforceSessionLimit(User $user): void
    {
        $sessions = $this->getUserSessions($user->id);

        if (count($sessions) >= self::MAX_SESSIONS_PER_USER) {
            // Evict the oldest session
            usort($sessions, fn($a, $b) => $a['created_at'] <=> $b['created_at']);
            $this->terminateSession($sessions[0]['id']);
        }
    }

    /**
     * Track a session under the user's session list.
     */
    protected function trackUserSession(User $user, string $sessionId): void
    {
        $key = $this->userSessionsKey($user->id);
        $sessions = Cache::get($key, []);
        $sessions[] = $sessionId;
        Cache::put($key, $sessions, self::SESSION_TTL);

        // Also track at tenant level for the dashboard
        $tenantId = $user->current_tenant_id;
        if ($tenantId) {
            $tenantKey = "mcp:tenant:{$tenantId}:sessions";
            $tenantSessions = Cache::get($tenantKey, []);
            $tenantSessions[] = $sessionId;
            Cache::put($tenantKey, $tenantSessions, self::SESSION_TTL);
        }
    }

    /**
     * Remove a session from the user's tracked session list.
     */
    protected function removeUserSession(string $userId, string $sessionId): void
    {
        $key = $this->userSessionsKey($userId);
        $sessions = Cache::get($key, []);
        $sessions = array_values(array_filter($sessions, fn($id) => $id !== $sessionId));
        Cache::put($key, $sessions, self::SESSION_TTL);
    }

    protected function sessionKey(string $sessionId): string
    {
        return "mcp:session:{$sessionId}";
    }

    protected function userSessionsKey(string $userId): string
    {
        return "mcp:user:{$userId}:sessions";
    }
}
