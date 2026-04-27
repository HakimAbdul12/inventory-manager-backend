<?php

namespace App\Http\Middleware;

use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantPermission
{
    private PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $tenant = app()->bound('current_tenant') ? app('current_tenant') : null;

        if (!$tenant) {
            return response()->json(['message' => 'No active tenant context.'], 403);
        }

        if (!$this->permissionService->userCan($permission, $user, $tenant)) {
            return response()->json([
                'message' => 'Forbidden',
                'error' => "You do not have the required permission: {$permission}"
            ], 403);
        }

        return $next($request);
    }
}
