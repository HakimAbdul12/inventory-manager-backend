<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the current tenant from the request and binds it to the app container.
 *
 * Priority:
 * 1. X-Tenant-ID header (for explicit switching)
 * 2. User's current_tenant_id (persistent preference)
 * 3. User's first tenant (fallback)
 */
class SetCurrentTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Resolve tenant ID from header or user preference
        $tenantId = $request->header('X-Tenant-ID')
            ?? $user->current_tenant_id;

        $tenant = null;

        if ($tenantId) {
            if ($user->is_super_admin) {
                $tenant = Tenant::find($tenantId);
            } else {
                // Validate user belongs to this tenant
                $tenant = $user->tenants()->where('tenants.id', $tenantId)->first();
            }
        }

        // Fallback: use first tenant if no valid tenant found
        if (!$tenant) {
            $tenant = $user->tenants()->first();

            // Update user's preference if we fell back
            if ($tenant && $user->current_tenant_id !== $tenant->id) {
                $user->update(['current_tenant_id' => $tenant->id]);
            }
        }

        if ($tenant) {
            // Bind tenant to app container for global scope access
            app()->instance('current_tenant', $tenant);

            // Determine the tenant role safely; super admins may not have a tenant pivot.
            $tenantRole = $user->is_super_admin
                ? 'super_admin'
                : ($tenant->pivot->role ?? $tenant->getMemberRole($user));

            app()->instance('current_tenant_role', $tenantRole);
        }

        return $next($request);
    }
}
