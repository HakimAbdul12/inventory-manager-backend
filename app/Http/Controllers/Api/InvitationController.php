<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantInvitation;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class InvitationController extends Controller
{
    /**
     * Show invitation details by token.
     */
    public function show(string $token): JsonResponse
    {
        $invitation = TenantInvitation::where('token', $token)
            ->whereNull('accepted_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->with(['tenant:id,name,logo', 'inviter:id,name'])
            ->firstOrFail();

        return response()->json([
            'data' => [
                'email' => $invitation->email,
                'name' => $invitation->name,
                'tenant' => $invitation->tenant,
                'inviter' => $invitation->inviter,
            ],
        ]);
    }

    /**
     * Accept an invitation.
     */
    public function accept(Request $request, string $token): JsonResponse
    {
        $invitation = TenantInvitation::where('token', $token)
            ->whereNull('accepted_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->firstOrFail();

        $user = User::where('email', $invitation->email)->first();

        // If user doesn't exist, they must provide registration details
        if (!$user) {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $invitation->email,
                'password' => Hash::make($validated['password']),
            ]);
            
            // Assign default system role
            $user->assignRole('dealer');

            // Log in the new user
            \Illuminate\Support\Facades\Auth::login($user);
        } else {
            // Existing user - check if they are logged in as someone else
            if (auth()->check() && auth()->user()->email !== $invitation->email) {
                return response()->json([
                    'message' => 'This invitation is for ' . $invitation->email . '. You are logged in as ' . auth()->user()->email . '.'
                ], 403);
            }

            // If not logged in, they must provide password
            if (!auth()->check()) {
                $request->validate([
                    'password' => ['required', 'string'],
                ]);

                if (!Hash::check($request->password, $user->password)) {
                    return response()->json([
                        'message' => 'Invalid password for this account.'
                    ], 401);
                }

                \Illuminate\Support\Facades\Auth::login($user);
            }
        }

        $tenant = $invitation->tenant;

        if ($tenant->hasMember($user)) {
            $invitation->update(['accepted_at' => now()]);
            // Still log them in if they weren't
            if (!auth()->check()) {
                 \Illuminate\Support\Facades\Auth::login($user);
            }
        } else {
            DB::transaction(function () use ($user, $tenant, $invitation) {
                // Add to tenant
                $tenant->addMember($user, 'viewer'); // Default legacy role

                // Assign specific roles
                if (!empty($invitation->role_ids)) {
                    $syncData = [];
                    foreach ($invitation->role_ids as $roleId) {
                        $syncData[$roleId] = [
                            'tenant_id' => $tenant->id,
                            'assigned_by' => $invitation->invited_by,
                        ];
                    }
                    $user->tenantRoles()->wherePivot('tenant_id', $tenant->id)->syncWithoutDetaching($syncData);
                }

                // Mark invitation as accepted
                $invitation->update(['accepted_at' => now()]);

                // Set as current tenant if none set
                if (!$user->current_tenant_id) {
                    $user->update(['current_tenant_id' => $tenant->id]);
                }
            });

            app(PermissionService::class)->clearUserCache($user, $tenant);
        }

        $user->refresh();
        $currentTenant = $user->currentTenant;

        return response()->json([
            'success' => true,
            'message' => 'Invitation accepted successfully.',
            'redirect' => '/dashboard',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames(),
                    'is_super_admin' => $user->is_super_admin,
                    'avatar' => $user->avatar,
                    'banner_image' => $user->banner_image,
                    'company_name' => $user->company_name,
                    'tenants' => $user->getTenantsForApi(),
                    'current_tenant' => $currentTenant ? [
                        'id' => $currentTenant->id,
                        'name' => $currentTenant->name,
                        'slug' => $currentTenant->slug,
                        'logo' => $currentTenant->logo,
                        'role' => $user->roleInTenant($currentTenant),
                    ] : null,
                ],
            ],
        ]);
    }
}
