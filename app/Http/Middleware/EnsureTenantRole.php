<?php

namespace App\Http\Middleware;

use App\Models\TenantUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures the authenticated user has at least the required role in the current tenant.
 *
 * Usage in routes: ->middleware('tenant.role:admin')
 * Accepts multiple: ->middleware('tenant.role:admin,manager')
 */
class EnsureTenantRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $tenant = app()->bound('current_tenant') ? app('current_tenant') : null;

        if (!$tenant) {
            return response()->json(['message' => 'No active tenant.'], 403);
        }

        $userRole = $tenant->pivot->role ?? $tenant->getMemberRole($user);

        if (!$userRole) {
            return response()->json(['message' => 'Not a member of this tenant.'], 403);
        }

        // Check if user's role is in the allowed list OR has a higher privilege
        $allowed = false;
        foreach ($roles as $requiredRole) {
            $requiredRole = trim($requiredRole);
            if (TenantUser::roleLevel($userRole) >= TenantUser::roleLevel($requiredRole)) {
                $allowed = true;
                break;
            }
        }

        if (!$allowed) {
            return response()->json([
                'message' => 'Insufficient role. Required: ' . implode(' or ', $roles),
            ], 403);
        }

        return $next($request);
    }
}
