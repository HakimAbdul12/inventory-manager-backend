<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantRole;
use App\Models\TenantUser;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TenantController extends Controller
{
    /**
     * List all tenants the user belongs to.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // If super admin, return all tenants (they may not be a member)
        if ($user->is_super_admin) {
            $all = Tenant::all()->map(function (Tenant $tenant) use ($user) {
                // Determine if the user is a member and include role if present
                $membership = $user->tenants()->where('tenants.id', $tenant->id)->first();
                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'logo' => $tenant->logo,
                    'banner_image' => $tenant->banner_image,
                    'role' => $membership?->pivot?->role ?? null,
                    'member_count' => $tenant->users()->count(),
                ];
            })->toArray();

            return response()->json([
                'data' => $all,
                'current_tenant_id' => $user->current_tenant_id,
            ]);
        }

        $tenants = $user->getTenantsForApi();

        return response()->json([
            'data' => $tenants,
            'current_tenant_id' => $user->current_tenant_id,
        ]);
    }

    /**
     * Create a new tenant (workspace).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'string', 'max:2048'],
        ]);

        $tenant = Tenant::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']) . '-' . Str::lower(Str::random(6)),
            'logo' => $validated['logo'] ?? null,
            'owner_id' => $request->user()->id,
            'settings' => [],
        ]);

        // Add the creator as owner
        $tenant->addMember($request->user(), TenantUser::ROLE_OWNER);

        // Seed default roles for this new tenant
        app(PermissionService::class)->syncDefaultRoles($tenant);

        // Switch to the new tenant
        $request->user()->switchTenant($tenant->id);

        return response()->json([
            'message' => 'Workspace created successfully.',
            'data' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'logo' => $tenant->logo,
                'banner_image' => $tenant->banner_image,
                'description' => $tenant->description,
                'role' => TenantUser::ROLE_OWNER,
                'member_count' => 1,
                'owner_id' => $tenant->owner_id,
                'created_at' => $tenant->created_at,
            ],
        ], 201);
    }

    /**
     * Update tenant details.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);

        // Only owners and admins can update
        $role = $tenant->getMemberRole($request->user());
        if (!in_array($role, Tenant::adminRoles())) {
            return response()->json(['message' => 'Insufficient permissions.'], 403);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'logo' => ['nullable', 'string', 'max:2048'],
            'banner_image' => ['nullable', 'string', 'max:2048'],
            'description' => ['nullable', 'string', 'max:5000'],
            'settings' => ['sometimes', 'array'],
        ]);

        $tenant->update($validated);
        $tenant->refresh();

        return response()->json([
            'message' => 'Workspace updated successfully.',
            'data' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'logo' => $tenant->logo,
                'banner_image' => $tenant->banner_image,
                'description' => $tenant->description,
                'owner_id' => $tenant->owner_id,
                'created_at' => $tenant->created_at,
                'role' => $role,
                'member_count' => $tenant->getMemberCount(),
            ],
        ]);
    }

    /**
     * Switch the user's active tenant.
     */
    public function switchTenant(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'string'],
        ]);

        $switched = $request->user()->switchTenant($validated['tenant_id']);

        if (!$switched) {
            return response()->json([
                'message' => 'You are not a member of this workspace.',
            ], 403);
        }

        $tenant = $request->user()->fresh()->currentTenant;

        return response()->json([
            'message' => 'Switched workspace successfully.',
            'data' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'logo' => $tenant->logo,
                'banner_image' => $tenant->banner_image,
                'description' => $tenant->description,
                'role' => $request->user()->roleInTenant($tenant),
                'member_count' => $tenant->getMemberCount(),
                'owner_id' => $tenant->owner_id,
                'created_at' => $tenant->created_at,
            ],
        ]);
    }

    /**
     * List members of a tenant.
     */
    public function members(Request $request, string $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);

        // Verify user belongs to this tenant
        if (!$tenant->hasMember($request->user())) {
            return response()->json(['message' => 'Not a member of this workspace.'], 403);
        }

        $members = $tenant->users()
            ->select('users.id', 'users.name', 'users.email', 'users.avatar')
            ->get()
            ->map(function (User $user) use ($tenant) {
                // Get assigned role records from tenant_user_roles
                $roles = $user->tenantRoles()
                    ->wherePivot('tenant_id', $tenant->id)
                    ->get()
                    ->map(fn(TenantRole $r) => [
                        'id' => $r->id,
                        'name' => $r->name,
                        'slug' => $r->slug,
                    ])
                    ->values();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'role' => $user->pivot->role, // legacy pivot role
                    'roles' => $roles, // granular role records
                    'joined_at' => $user->pivot->joined_at,
                ];
            });

        return response()->json(['data' => $members]);
    }

    /**
     * Get a specific member's profile.
     */
    public function getMember(Request $request, string $tenantId, string $userId): JsonResponse
    {
        $tenant = Tenant::findOrFail($tenantId);

        // Verify user belongs to this tenant
        if (!$tenant->hasMember($request->user())) {
            return response()->json(['message' => 'Not a member of this workspace.'], 403);
        }

        $user = $tenant->users()->findOrFail($userId);

        // Get assigned role records from tenant_user_roles
        $roles = $user->tenantRoles()
            ->wherePivot('tenant_id', $tenant->id)
            ->get()
            ->map(fn(TenantRole $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'slug' => $r->slug,
            ])
            ->values();

        $memberData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'bio' => $user->bio,
            'phone' => $user->phone,
            'location_city' => $user->location_city,
            'location_country' => $user->location_country,
            'specialties' => $user->specialties,
            'years_in_business' => $user->years_in_business,
            'social_links' => $user->social_links,
            'last_active_at' => $user->last_active_at,
            'is_public_profile' => $user->is_public_profile,
            'role' => $user->pivot->role, // legacy pivot role
            'roles' => $roles, // granular role records
            'joined_at' => $user->pivot->joined_at,
        ];

        return response()->json(['data' => $memberData]);
    }

    /**
     * Invite a member via email.
     * Creates a pending invitation and sends a link.
     */
    public function inviteMember(Request $request, string $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);
        $permService = app(PermissionService::class);

        // Check if user has permission to invite
        if (!$permService->userCan('workspace.invite', $request->user(), $tenant)) {
            return response()->json(['message' => 'Insufficient permissions to invite members.'], 403);
        }

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'role_ids' => ['required', 'array'],
            'role_ids.*' => ['string', 'exists:tenant_roles,id'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $email = $validated['email'];
        $user = User::where('email', $email)->first();

        if ($user && $tenant->hasMember($user)) {
            return response()->json(['message' => 'User is already a member of this workspace.'], 422);
        }

        // Check for active invitation
        $existingInvite = \App\Models\TenantInvitation::where('tenant_id', $tenant->id)
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if ($existingInvite) {
            return response()->json(['message' => 'An invitation has already been sent to this email.'], 422);
        }

        $invitation = \App\Models\TenantInvitation::create([
            'tenant_id' => $tenant->id,
            'email' => $email,
            'name' => $validated['name'] ?? null,
            'role_ids' => $validated['role_ids'],
            'token' => \Illuminate\Support\Str::random(40),
            'invited_by' => $request->user()->id,
            'expires_at' => now()->addDays(7),
        ]);

        $roleNames = \App\Models\TenantRole::withoutGlobalScope('tenant')
            ->whereIn('id', $validated['role_ids'])
            ->pluck('name')
            ->toArray();

        try {
            \Illuminate\Support\Facades\Mail::to($email)->send(new \App\Mail\TenantInvitation(
                $tenant,
                $request->user(),
                $invitation,
                $roleNames
            ));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to send invitation email to {$email}: " . $e->getMessage());
        }

        return response()->json([
            'message' => 'Invitation sent successfully.',
            'data' => [
                'email' => $email,
                'expires_at' => $invitation->expires_at,
            ],
        ], 201);
    }

    /**
     * Add a member directly to the workspace.
     * If the user doesn't exist, register them first.
     */
    public function addMember(Request $request, string $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);
        $permService = app(PermissionService::class);

        if (!$permService->userCan('workspace.invite', $request->user(), $tenant)) {
            return response()->json(['message' => 'Insufficient permissions.'], 403);
        }

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'role' => ['sometimes', \Illuminate\Validation\Rule::in(Tenant::validRoles())],
            'role_ids' => ['sometimes', 'array'],
            'role_ids.*' => ['string', 'exists:tenant_roles,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8'],
            'avatar' => ['nullable', 'image', 'max:5120'],
        ]);

        $legacyRole = $validated['role'] ?? 'viewer';
        if ($legacyRole === \App\Models\TenantUser::ROLE_OWNER) {
            return response()->json(['message' => 'Cannot assign owner role.'], 422);
        }

        $user = User::where('email', $validated['email'])->first();
        $wasRegistered = false;

        if (!$user) {
            if (empty($validated['name']) || empty($validated['password'])) {
                return response()->json([
                    'message' => 'User does not exist. Name and password required for direct addition.',
                ], 404);
            }

            $userData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
            ];

            if ($request->hasFile('avatar')) {
                $userData['avatar'] = $request->file('avatar')->store('avatars', 'public');
            }

            $user = User::create($userData);
            $user->assignRole('dealer');
            $wasRegistered = true;
        }

        if ($tenant->hasMember($user)) {
            return response()->json(['message' => 'User is already a member.'], 422);
        }

        $tenant->addMember($user, $legacyRole);

        if (!empty($validated['role_ids'])) {
            $syncData = [];
            foreach ($validated['role_ids'] as $roleId) {
                $role = TenantRole::withoutGlobalScope('tenant')->find($roleId);
                if ($role && $role->tenant_id === $tenant->id && $role->slug !== 'owner') {
                    $syncData[$roleId] = [
                        'tenant_id' => $tenant->id,
                        'assigned_by' => $request->user()->id,
                    ];
                }
            }
            $user->tenantRoles()->wherePivot('tenant_id', $tenant->id)->sync($syncData);
        } else {
            $permService->assignRoleBySlug($user, $tenant, $legacyRole);
        }

        $permService->clearUserCache($user, $tenant);

        return response()->json([
            'message' => $wasRegistered ? 'User registered and added.' : 'User added to workspace.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ], 201);
    }

    /**
     * Update a member's role.
     */
    public function updateMember(Request $request, string $tenantId, string $userId): JsonResponse
    {
        $tenant = Tenant::findOrFail($tenantId);

        // Only admins+ can update roles
        $requesterRole = $tenant->getMemberRole($request->user());
        if (!in_array($requesterRole, Tenant::adminRoles())) {
            return response()->json(['message' => 'Insufficient permissions.'], 403);
        }

        $validated = $request->validate([
            'role' => ['sometimes', Rule::in(Tenant::validRoles())],
            'role_ids' => ['sometimes', 'array'],
            'role_ids.*' => ['string', 'exists:tenant_roles,id'],
        ]);

        $user = User::findOrFail($userId);

        if (!$tenant->hasMember($user)) {
            return response()->json(['message' => 'User is not a member.'], 404);
        }

        // Cannot change the owner's role
        $currentRole = $tenant->getMemberRole($user);
        if ($currentRole === TenantUser::ROLE_OWNER && $requesterRole !== TenantUser::ROLE_OWNER) {
            return response()->json(['message' => 'Cannot change the owner\'s role.'], 403);
        }

        // Update legacy role if provided
        if (isset($validated['role'])) {
            if ($validated['role'] === TenantUser::ROLE_OWNER && $requesterRole !== TenantUser::ROLE_OWNER) {
                return response()->json(['message' => 'Only owners can transfer ownership.'], 403);
            }
            $tenant->updateMemberRole($user, $validated['role']);
        }

        // Sync granular role records if role_ids provided
        if (isset($validated['role_ids'])) {
            $syncData = [];
            foreach ($validated['role_ids'] as $roleId) {
                $role = TenantRole::withoutGlobalScope('tenant')->find($roleId);
                if ($role && $role->tenant_id === $tenant->id) {
                    $syncData[$roleId] = [
                        'tenant_id' => $tenant->id,
                        'assigned_by' => $request->user()->id,
                    ];
                }
            }
            $user->tenantRoles()->wherePivot('tenant_id', $tenant->id)->sync($syncData);
            app(PermissionService::class)->clearUserCache($user, $tenant);
        }

        // Return updated roles
        $assignedRoles = $user->tenantRoles()
            ->wherePivot('tenant_id', $tenant->id)
            ->get()
            ->map(fn(TenantRole $r) => ['id' => $r->id, 'name' => $r->name, 'slug' => $r->slug])
            ->values();

        return response()->json([
            'message' => 'Member updated.',
            'data' => [
                'role' => $tenant->getMemberRole($user),
                'roles' => $assignedRoles,
            ],
        ]);
    }

    /**
     * Remove a member from a tenant.
     */
    public function removeMember(Request $request, string $tenantId, string $userId): JsonResponse
    {
        $tenant = Tenant::findOrFail($tenantId);

        // Only admins+ can remove members
        $requesterRole = $tenant->getMemberRole($request->user());
        if (!in_array($requesterRole, Tenant::adminRoles())) {
            return response()->json(['message' => 'Insufficient permissions.'], 403);
        }

        $user = User::findOrFail($userId);

        // Cannot remove the owner
        if ($tenant->getMemberRole($user) === TenantUser::ROLE_OWNER) {
            return response()->json(['message' => 'Cannot remove the workspace owner.'], 403);
        }

        // Cannot remove yourself
        if ((int) $userId === (int) $request->user()->id) {
            return response()->json(['message' => 'Cannot remove yourself. Use leave instead.'], 422);
        }

        $tenant->removeMember($user);

        return response()->json(['message' => 'Member removed.']);
    }

    /**
     * Upload a tenant banner image.
     */
    public function uploadBanner(Request $request, string $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);

        $role = $tenant->getMemberRole($request->user());
        if (!in_array($role, Tenant::adminRoles())) {
            return response()->json(['message' => 'Insufficient permissions.'], 403);
        }

        $request->validate([
            'banner' => ['required', 'image', 'max:10240'], // 10MB
        ]);

        // Delete old banner if exists
        if ($tenant->banner_image) {
            Storage::disk('public')->delete($tenant->banner_image);
        }

        $path = $request->file('banner')->store('tenant-banners', 'public');
        $tenant->update(['banner_image' => $path]);

        return response()->json([
            'message' => 'Banner uploaded.',
            'banner_image' => $path,
        ]);
    }

    /**
     * Upload a tenant logo.
     */
    public function uploadLogo(Request $request, string $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);

        $role = $tenant->getMemberRole($request->user());
        if (!in_array($role, Tenant::adminRoles())) {
            return response()->json(['message' => 'Insufficient permissions.'], 403);
        }

        $request->validate([
            'logo' => ['required', 'image', 'max:2048'], // 2MB
        ]);

        if ($tenant->logo) {
            Storage::disk('public')->delete($tenant->logo);
        }

        $path = $request->file('logo')->store('tenant-logos', 'public');
        $tenant->update(['logo' => $path]);

        return response()->json([
            'message' => 'Logo uploaded.',
            'logo' => $path,
        ]);
    }

    /**
     * Delete a tenant (owner only).
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);

        if ($tenant->owner_id !== $request->user()->id) {
            return response()->json(['message' => 'Only the workspace owner can delete it.'], 403);
        }

        // Clean up storage
        if ($tenant->banner_image) {
            Storage::disk('public')->delete($tenant->banner_image);
        }
        if ($tenant->logo) {
            Storage::disk('public')->delete($tenant->logo);
        }

        // Switch user to another tenant if this was their current one
        $user = $request->user();
        if ($user->current_tenant_id === $tenant->id) {
            $otherTenant = $user->tenants()->where('tenants.id', '!=', $tenant->id)->first();
            $user->update(['current_tenant_id' => $otherTenant?->id]);
        }

        $tenant->delete();

        return response()->json(['message' => 'Workspace deleted.']);
    }

    /**
     * Get all roles for a tenant.
     */
    public function roles(string $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);

        $roles = TenantRole::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->with('permissions:id,key')
            ->get()
            ->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'slug' => $role->slug,
                    'description' => $role->description,
                    'is_system' => $role->is_system,
                    'level' => $role->level,
                    'permissions' => $role->permissions->pluck('key'),
                ];
            });

        return response()->json(['data' => $roles]);
    }

    /**
     * Create a new custom role.
     */
    public function createRole(Request $request, string $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);
        
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:tenant_permissions,key'],
        ]);

        $slug = Str::slug($validated['name']);
        
        // Ensure slug is unique for this tenant
        if (TenantRole::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->where('slug', $slug)->exists()) {
            return response()->json(['message' => 'A role with this name already exists.'], 422);
        }

        $role = TenantRole::withoutGlobalScope('tenant')->create([
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'is_system' => false,
            'level' => 0, // Custom roles default to lowest hierarchy level
        ]);

        if (!empty($validated['permissions'])) {
            // Ensure tenant roles can only include high-level permissions
            $perms = \App\Models\TenantPermission::whereIn('key', $validated['permissions'])->get();
            $invalid = $perms->where('type', 'low')->pluck('key')->toArray();
            if (!empty($invalid)) {
                return response()->json(['message' => 'Cannot assign system-level permissions to tenant roles.', 'invalid' => $invalid], 422);
            }
            $permIds = $perms->pluck('id');
            $role->permissions()->sync($permIds);
        }

        return response()->json([
            'message' => 'Role created successfully.',
            'data' => array_merge($role->toArray(), ['permissions' => $validated['permissions'] ?? []])
        ], 201);
    }

    /**
     * Update a role's details and permissions.
     */
    public function updateRole(Request $request, string $id, string $roleId): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);
        $role = TenantRole::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->findOrFail($roleId);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'exists:tenant_permissions,key'],
        ]);

        $updateData = [];
        if (isset($validated['name'])) {
            $updateData['name'] = $validated['name'];
            if (!$role->is_system) {
                // System role slugs should not be changed
                $updateData['slug'] = Str::slug($validated['name']);
            }
        }
        if (array_key_exists('description', $validated)) {
            $updateData['description'] = $validated['description'];
        }

        if (!empty($updateData)) {
            $role->update($updateData);
        }

        if (isset($validated['permissions'])) {
            // Ensure tenant roles can only include high-level permissions
            $perms = \App\Models\TenantPermission::whereIn('key', $validated['permissions'])->get();
            $invalid = $perms->where('type', 'low')->pluck('key')->toArray();
            if (!empty($invalid)) {
                return response()->json(['message' => 'Cannot assign system-level permissions to tenant roles.', 'invalid' => $invalid], 422);
            }
            $permIds = $perms->pluck('id');
            $role->permissions()->sync($permIds);
            
            // Clear cache for all users with this role in this tenant
            $userIds = $role->users()->pluck('users.id');
            foreach ($userIds as $userId) {
                $user = User::find($userId);
                if ($user) {
                    app(PermissionService::class)->clearUserCache($user, $tenant);
                }
            }
        }

        return response()->json(['message' => 'Role updated successfully.']);
    }

    /**
     * Delete a custom role.
     */
    public function deleteRole(Request $request, string $id, string $roleId): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);
        $role = TenantRole::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->findOrFail($roleId);

        if ($role->is_system) {
            return response()->json(['message' => 'System roles cannot be deleted.'], 403);
        }

        if ($role->users()->count() > 0) {
            return response()->json(['message' => 'Cannot delete a role that is assigned to users.'], 422);
        }

        $role->delete();

        return response()->json(['message' => 'Role deleted successfully.']);
    }

    /**
     * Assign specific roles to a user in a tenant.
     */
    public function assignUserRoles(Request $request, string $tenantId, string $userId): JsonResponse
    {
        $tenant = Tenant::findOrFail($tenantId);
        $user = User::findOrFail($userId);

        if (!$tenant->hasMember($user)) {
            return response()->json(['message' => 'User is not a member of this workspace.'], 404);
        }

        $validated = $request->validate([
            'role_ids' => ['required', 'array'],
            'role_ids.*' => ['string', 'exists:tenant_roles,id'],
        ]);

        // Sync via pivot table
        $syncData = [];
        foreach ($validated['role_ids'] as $roleId) {
            // Verify role belongs to this tenant
            $role = TenantRole::withoutGlobalScope('tenant')->find($roleId);
            if ($role && $role->tenant_id === $tenant->id) {
                $syncData[$roleId] = ['tenant_id' => $tenant->id, 'assigned_by' => $request->user()->id];
            }
        }

        $user->tenantRoles()->wherePivot('tenant_id', $tenant->id)->sync($syncData);

        // Clear user's permission cache
        app(PermissionService::class)->clearUserCache($user, $tenant);

        return response()->json(['message' => 'Roles assigned successfully.']);
    }
}
